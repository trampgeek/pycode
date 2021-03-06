# Simple check on the pypy sandbox set up for running the Moodle 2 pycode tests.

import base64
import json
import os
import marshal
import subprocess

studentCode = "def num_rushes(a,b,c):\n x = 0.95\n y = 0.95\n return 1"
expression = "print num_rushes(10, 10, 9)"
result = "1"
testSetEncoded = base64.encodestring(json.dumps([studentCode, [(expression, result)]]))
print testSetEncoded

path = "/usr/local/pypy-sandbox-4-pycode/pypy/translator/sandbox/pycodeTest.py"
try:
    subproc = subprocess.Popen([path, testSetEncoded], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    (result, stderr) = subproc.communicate()
    print "Output:", result
    if stderr != '':
       print "Stderr:", stderr
    lines = result.split('\n')[:-1]
    for i in range(0, len(lines), 2):
        print lines[i]
        print lines[i+1].decode('hex')
except subprocess.CalledProcessError, e:
    print "*** Error on invoking sandbox: ", e

