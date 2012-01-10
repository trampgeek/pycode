#! /usr/bin/env python

'''A modified version of pypy_interact, the controller routine
   for the pypy sandbox, for use with the Moodle Python quiz question.
   This version takes a single base64 encoded string as a parameter, 
   the unencoded string being a JSON encoding of all tests to
   be performed, in the form (studentResponse, test) where tests is
   a list of (expression, expected) pairs.
    
   For each test, the tester execute the
   student's code (which is generally expected to define a specified
   function), evaluates the given expression and tests the result of
   the evaluation for equality with the expected result.
   Output from each test is two lines:
   1.  'Yes', 'No' or 'Error' depending on whether the expression 
       correctly evaluates to the required result or to a different
       result or causes some sort of runtime error/exception, resp.
   2.  In the case of 'Yes' or 'No' the Python 'repr' of the result of
       evaluating the test expression or, in the case of 'Error', a text
       string indicating what error occurred (usually the string
       accompanying an exception). Furthermore, an error causes an
       immediate abort -- further testing is not performed.
   The test expression is assumed not to print anything. If the student's
   code generates any print output, the expression result is ignored
   and the returned result is an 'Error' line followed by 'Printing
   not allowed'.
   

Usage:
    pycodeTest [options] base64encodedjsonencodedtests

Options:
    --heapsize=N  limit memory usage to N bytes, or kilo- mega- giga-bytes
                  with the 'k', 'm' or 'g' suffix respectively.
                  ATM this only works with PyPy translated with Boehm or
                  the semispace or generation GCs.
    --timeout=N   limit execution time to N (real-time) seconds.
    --log=FILE    log all user input into the FILE
'''

import os
import shutil
import sys
import autopath
import json
from base64 import b64decode
import marshal
sys.stderr = sys.stdout

from time import time
from shutil import rmtree
from tempfile import mkstemp, mkdtemp
from pypy.translator.sandbox.sandlib import SimpleIOSandboxedProc, VirtualizedSandboxedProc
from pypy.translator.sandbox.vfs import Dir, RealDir, RealFile
from pypy.tool.lib_pypy import LIB_ROOT

CURR_DIR = os.path.dirname(os.path.realpath(__file__))
EXECUTABLE = os.path.join(CURR_DIR, "pypy-sandbox")

class PyPySandboxedProc(VirtualizedSandboxedProc, SimpleIOSandboxedProc):
    argv0 = '/bin/pypy-c'
    virtual_cwd = '/tmp'
    virtual_env = {}
    virtual_console_isatty = True

    def __init__(self, executable, arguments, tmpdir=None):
        self.executable = executable = os.path.abspath(executable)
        self.tmpdir = tmpdir
        super(PyPySandboxedProc, self).__init__([self.argv0] + arguments,
                                                executable=executable)

    def build_virtual_root(self):
        # build a virtual file system:
        # * can access its own executable
        # * can access the pure Python libraries
        # * can access the temporary usession directory as /tmp
        exclude = ['.pyc', '.pyo']
        if self.tmpdir is None:
            tmpdirnode = Dir({})
        else:
            tmpdirnode = RealDir(self.tmpdir, exclude=exclude)
        libroot = str(LIB_ROOT)

        return Dir({
            'bin': Dir({
                'pypy-c': RealFile(self.executable),
                'lib-python': RealDir(os.path.join(libroot, 'lib-python'),
                                      exclude=exclude), 
                'lib_pypy': RealDir(os.path.join(libroot, 'lib_pypy'),
                                      exclude=exclude),
                }),
             'tmp': tmpdirnode
             })


def signal_name(n):
    import signal
    for key, value in signal.__dict__.items():
        if key.startswith('SIG') and not key.startswith('SIG_') and value == n:
            return key
    return 'signal %d' % (n,)


def escape(s):
    ''' Return s with any double quotes replaced by '\"' '''
    return s.replace('"', r'\"')

def clean(s):
    ''' Strip trailing new lines and replace embedded newlines
        with '\n' strings to make a readable string'''
    while s.endswith('\n'):
        s = s[:-1]
    s = s.replace('\n', r'\n')
    return s
        
def indent(multilineString):
    '''Indent all lines by four spaces'''
    return '    ' + multilineString.replace('\n', '\n    ')

def makeTester(testSpec):
    '''Given the base64 encoded json encoded representation of a test,
       construct a program to do the tests. Return that program as
       a string'''
    tester = '''
from pycodeClasses import PycodeTester
import marshal

testSpec = """{0}""".decode('hex')
studentCode, testList = marshal.loads(testSpec)

pt = PycodeTester(studentCode)
results = pt.runTests(testList)
for (outcome, output) in results:
    print outcome
    print output.encode('hex')
    
'''.format(testSpec)
    return tester
    

if __name__ == '__main__':
    HEAP_MB = 50  # Default heap size (MB)
    try:
        vfsRoot = None
        from getopt import gnu_getopt      # In this version options apply only to this prog
        options, arguments = gnu_getopt(sys.argv[1:], '', 
                                    ['heapsize=', 'timeout=', 'log='])

        timeout = 5  # Default timeout of 5 sec
        logfile = None
        bytes = HEAP_MB * 1024 * 1024  # Default heap size of 50MB

        for option, value in options:
            if option == '--heapsize':
                value = value.lower()
                if value.endswith('k'):
                    bytes = int(value[:-1]) * 1024
                elif value.endswith('m'):
                    bytes = int(value[:-1]) * 1024 * 1024
                elif value.endswith('g'):
                    bytes = int(value[:-1]) * 1024 * 1024 * 1024
                else:
                    bytes = int(value)
                if bytes <= 0:
                    raise ValueError
                if bytes > sys.maxint:
                    raise OverflowError("--heapsize maximum is %d" % sys.maxint)
            elif option == '--timeout':
                timeout = int(value)
            elif option == '--log':
                logfile = value    
            else:
                raise ValueError(option)

        if len(arguments) != 1:
            raise Exception("Bad call to pycodeTest.py: {0} args".format(str(len(arguments))))

        # Bug in sandbox prevents passing arguments[0] directly through
        # to sandbox program, so decode then reserialize with marshal instead
        
        testSpec = marshal.dumps(json.loads(b64decode(arguments[0]))).encode('hex')
        vfsRoot = mkdtemp(dir="/tmp", prefix="pycode_")
        shutil.copy(CURR_DIR + '/pycodeClasses.py', vfsRoot)
        (pytestfnum, pytestfname) = mkstemp(suffix=".py", prefix="pyblah", dir=vfsRoot)
        pytestf = os.fdopen(pytestfnum, "w")
        testFile = makeTester(testSpec)
        pytestf.write(testFile)

# Now execute the test file on the pypy sandbox

        pytestf.close()
        fname = pytestfname.split('/')[-1]

        # Note addition of -S option to prevent loading of site directory.
        # See email/pypy blog from Ned Batchelder 10 - 12 Dec 2011.
        args = ['--heapsize', str(bytes), '-S'] + [fname]
        # now = time()
        
        sandproc = PyPySandboxedProc(EXECUTABLE, args, tmpdir=vfsRoot)
        sandproc.settimeout(timeout, interrupt_main=True)
        if logfile is not None:
            sandproc.setlogfile(logfile)
        
        try:
            returnCode = sandproc.interact()
            if returnCode > 0:
                raise Exception("Unknown error")
            if returnCode < 0:
                raise Exception(signal_name(-returnCode) + " (timeout or too much memory?)")
        finally:
            sandproc.kill()
            
        # print "Sandbox time: ", time() - now
            
    except Exception, e:
        print 'Runtime Error'
        print str(e).encode('hex')
        
    finally:
        if vfsRoot is not None:
            #rmtree(vfsRoot)
            pass





