import code
import sys

class PycodeConsole (code.InteractiveConsole):
    def __init__(self):
        environ = {'__name__': 'PycodeTester',
                   '__doc__': None,
                   'raw_input': self.raw_input
                  }
        code.InteractiveConsole.__init__(self, environ)
        self.output = ''
        self.syntaxError = self.exception = False
        self.lineNum = 0
        self.inputLines = None
            
        
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




class PycodeTester (object):
    def __init__(self, code):
        if code.endswith('\n'):
            self.studentCode = code
        else:
            self.studentCode = code + '\n'
        
    def runTests(self, tests):
        '''Run the given set of tests with the code provided to the
           constructor. Returns a list of result pairs, each consisting
           of the string 'Yes', 'No', 'Syntax Error' or 'Runtime Error'
           and the actual output received. 'Yes' indicates that the
           output matches the expected value. The strings are deemed
           to match if either they're equal or (the expected string
           does not end with a newline and the actual output does and
           expected + '\n == output).
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
            elif output == expected:
                outcome = 'Yes'
            elif output.endswith('\n') and not expected.endswith('\n') \
                  and (expected + '\n' == output):
                outcome = 'Yes'
                output = output[:-1]
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
