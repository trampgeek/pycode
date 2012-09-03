import code
import sys

class PycodeConsole (code.InteractiveConsole):
    def __init__(self):
        environ = {'__name__': 'PycodeTester',
                   '__doc__': None,
                   'raw_input': self.raw_input,
                   'open': self.open
                  }
        code.InteractiveConsole.__init__(self, environ)
        self.output = ''
        self.syntaxError = self.exception = False
        self.lineNum = 0
        self.inputLines = None
       
        
    def open(self, filename, mode = 'r'):
        '''Open a file for reading or writing. See 'PycodeFile' class below'''
        return PycodeFile(filename, mode)
                
        
    def setInput(self, inputString):
        if inputString == None:
            self.inputLines = None
        else:
            inputString.replace('\r', '')
            while inputString.endswith('\n'):
                inputString = inputString[:-1]
            self.inputLines = inputString.split('\n')
        self.lineNum = 0
        
        
    def write(self, data):
        self.output += data
        
        
    def showsyntaxerror(self, *args):
        self.syntaxError = True
        code.InteractiveConsole.showsyntaxerror(self, *args)
        
        
    def showtraceback(self, *args):
        self.exception = True
        code.InteractiveConsole.showtraceback(self, *args)
    
        
    def runsource(self, src, filename='<input>', mode='single'):
        self.saved_stdout = sys.stdout
        self.saved_stderr = sys.stderr
        sys.stdout = self
        sys.stderr = self
        result = code.InteractiveConsole.runsource(self, src, filename, mode)
        sys.stdout = self.saved_stdout
        sys.stderr = self.saved_stderr
        return result

    
    def raw_input(self, prompt = ''):
        self.output += prompt
        if self.inputLines is None or self.lineNum >= len(self.inputLines):
            raise EOFError
        line = self.inputLines[self.lineNum]
        #self.output += "<<{0:d}>>".format(self.lineNum)
        self.lineNum += 1
        return line

    
    def runTest(self, studentCode, interpreterCommands, stdin=None):
        self.setInput(stdin)
        cmdLines = interpreterCommands.split('\n')
        cmdResult = self.runsource(studentCode, mode='exec')
        if cmdResult:
            self.syntaxError = True
            self.output += "Program code incomplete (unclosed brackets?)\n"
            
        self.commandsRun = 0
        cmdResult = False

        while self.commandsRun < len(cmdLines) and not self.syntaxError and not self.exception:
            line = cmdLines[self.commandsRun]
            cmdResult = self.push(line)
            self.commandsRun += 1
        while cmdResult:
            cmdResult = self.push('')   # close off any incomplete input
        return self.output


'''The PycodeFile class implements a rudimentary flat-file system so that
   questions can be set that ask students to read or write files.
   The only open modes permitted are r, w and a, with an optional b
   that is ignored (i.e., r+, w+, a+ are not allowed). 
'''
class PycodeFile:
    file_sys = {}  # A map from file name to string contents of file
    
    def __init__(self, filename, mode):
        mode = mode.replace('b', '')
        self.filename = filename
        self.mode = mode
        self.closed = False
        if mode not in ['r', 'w', 'a']:
            raise ValueError("Bad call to 'open'. In Pycode, mode string must be one of 'r', 'w', or 'a'")
        if mode == 'r' and filename not in PycodeFile.file_sys:
            raise IOError('File {0} not found'.format(filename))
        if mode == 'w' or (mode == 'a' and filename not in PycodeFile.file_sys):
            PycodeFile.file_sys[filename] = ''
        if mode != 'a':
            self.file_pos = 0
        else:
            self.file_pos = len(PycodeFile.file_sys[filename])
        
    def read(self, n = -1):
        self.check_open('r')
        contents = PycodeFile.file_sys[self.filename]
        if n >= 0:
            end_pos = min(len(contents), self.file_pos + n)
        else:
            end_pos = len(contents)
        result = contents[self.file_pos : end_pos]
        self.file_pos = end_pos
        return result
      
    def readline(self):
        contents = PycodeFile.file_sys[self.filename]
        if self.file_pos >= len(contents):
            return ''
        else:	
            n = contents.find('\n', self.file_pos)
            if n != -1: # If found a newline ...
                n += 1   # Adjust count to include newline itself
            return self.read(n - self.file_pos)
       
    def readlines(self):
        result = []
        line = self.readline()
        while line != '':
            result.append(line)
            line = self.readline()
        return result
    
    def seek(self, offset, whence = 0):
        file_length = len(PycodeFile.file_sys[self.filename])
        if whence == 0:
            self.file_pos = offset
        elif whence == 1:
            self.file_pos += offset
        elif whence == 2:
            self.file_pos = file_length + offset
        else:
            raise ValueError("Undefined 'whence' value for seek")
        self.file_pos = min(max(0, self.file_pos), file_length)
            
    def check_open(self, reqd_mode):
        if self.closed:
            raise ValueError('Attempt to read or write a closed file')
        if reqd_mode == 'r' and self.mode != 'r':
            raise IOError('Attempt to read from a file open for writing')
        if reqd_mode != 'r' and self.mode == 'r':
            raise IOError('Attempt to write to or truncate a file open for reading')
          
    def __iter__(self):
        return self

    def next(self):
        line = self.readline()
        if line:
            return line
        else:
            raise StopIteration

    def close(self):
        self.closed = True

    def __enter__(self):
        pass

    def __exit__(self, exc_type, exc_value, traceback):
        self.close()
        
    def flush(self):
        pass
    
    def write(self, s):
        self.check_open('w')
        contents = PycodeFile.file_sys[self.filename]
        new_contents = contents[:self.file_pos] + s
        PycodeFile.file_sys[self.filename] = new_contents
        self.file_pos = len(new_contents)
    
    def writelines(self, lines):
        for line in lines:
            self.write(line)
            
    def tell(self):
        return self.file_pos

    def truncate(self, size = None):
        self.check_open('w')
        if size is None:
            size = self.file_pos
        contents = PycodeFile.file_sys[self.filename]
        if size < len(contents):
            PycodeFile.file_sys[self.filename] = contents[:size]
   


class PycodeTester (object):
    def __init__(self, code):
        if code.endswith('\n'):
            self.studentCode = code
        else:
            self.studentCode = code + '\n'
            
            
    def stripTrailingWs(self, s):
        '''Return s with trailing whitespace stripped'''
        while s.endswith(('\n', ' ')):
            s = s[0:-1]
        return s
        
        
    def runTests(self, tests):
        '''Run the given set of tests with the code provided to the
           constructor. Returns a list of result pairs, each consisting
           of the string 'Yes', 'No', 'Syntax Error' or 'Runtime Error'
           and the actual output received. 'Yes' indicates that the
           output matches the expected value. Trailing whitespace is
           removed prior to the equality test. Leading whitespace,
           or trailing whitespace on lines other than the first, is
           not removed.
           '''
        results = []
        i = 0
        abort = False
        while i < len(tests) and not abort:
            if len(tests[i]) == 2:
                (testInput, expected) = tests[i]
                stdin = None
            else:
                (testInput, stdin, expected) = tests[i]
            pc = PycodeConsole()
            output = pc.runTest(self.studentCode, testInput, stdin)
            if pc.syntaxError:
                outcome = 'Syntax Error'
            elif pc.exception:
                outcome = 'Runtime Error'
            elif self.stripTrailingWs(output) == self.stripTrailingWs(expected):
                outcome = 'Yes'
            else:
                outcome = 'No'  # debug: + ' ' + comparison(output, expected)
            results.append( (outcome, output) )
            if pc.syntaxError or pc.exception:
                abort = True
            i += 1
            
        return results

# Utility function to show how two strings differ

def comparison(s1, s2):
    result = ''
    if len(s1) != len(s2):
        result += " (lengths: {0}, {1})".format(len(s1), len(s2))
    for i in range(0, min(len(s1), len(s2))):
        if s1[i] != s2[i]:
            result += ' differ at pos' + str(i)
            break
    return result
    
