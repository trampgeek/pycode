from pycodeClasses import PycodeConsole, PycodeTester
            
runs = []

#-------1. Right answer
src = '''def hello(name):
  return "Hello " + name
'''
tests = [
    ("hello('')", "", "'Hello '"),
    ("hello('Richard')", "", "'Hello Richard'"),
    ("hello('Richard Lobb')", "", "'Hello Richard Lobb'"),
    ("name = 'Richard'\nhello(name)", "", "'Hello Richard'"),
    ("name = 'Richard'\nname\nprint name\nhello(name)", "", "'Richard'\nRichard\n'Hello Richard'")
]
    
runs.append((src, tests))

#------2. Syntax error ----------
src = '''def hello(name)
  return "Hello " + name
'''
tests = [
    ("hello('')", "", "'Hello '"),
    ("hello('Richard')", "", "'Hello Richard'"),
    ("hello('Richard Lobb')", "", "'Hello Richard Lobb'")
]
runs.append((src, tests))

#------3. Runtime error ----------
src = '''def hello(name):
  return "Hello " + namex
'''
tests = [
    ("hello('')", "", "'Hello '"),
    ("hello('Richard')", "", "'Hello Richard'"),
    ("hello('Richard Lobb')", "", "'Hello Richard Lobb'")
]
runs.append((src, tests))

#------4. Belated runtime error ----------
src = '''def hello(name):
  if name != 'Richard Lobb':
      return "Hello " + name
  else:
      return namex
'''
tests = [
    ("hello('')", "", "'Hello '"),
    ("hello('Richard')", "", "'Hello Richard'"),
    ("hello('Richard Lobb')", "", "'Hello Richard Lobb'")
]
runs.append((src, tests))


#------5. Hello world ----------
src = '''print "Hello world"
'''
tests = [
    ('', "", "Hello world"),
]
runs.append((src, tests))

#------6. Echo input ----------
src = '''line = 0
try:
  while True:
     s = raw_input("Hi %d: " % line)
     print s
     line += 1
except EOFError:
  pass
'''

tests = [
    ("", "This is\nmy input\n", "Hi 0: <<0>>This is\nHi 1: <<1>>my input\nHi 2: ")
]

runs.append((src, tests))


# ==== Run all tests ======

def clean(s):
    if s.endswith('\n'):
        s = s[:-1]
    s = s.replace('\n', r'\n')
    if len(s) > 2222:
        s = s[:17] + '...'
    return s



testNum = 0
for (src, tests) in runs:
    testNum += 1
    pt = PycodeTester(src)
    results = pt.runTests(tests)
    print "\n\nTest number", testNum
    print src

    for i in range(len(tests)):
        (testInput, stdin, expected) = tests[i]
        testInputClean = clean(testInput)
        if i < len(results):
            (outcome, output) = results[i]
            outputClean = clean(output)
            expectedClean = clean(expected)
            print "{0:24s} {1:24s} {2:14s} {3:24s}".format(testInputClean, expectedClean, outcome, outputClean)
        else:
            print "{0:24s} {1:24s}       None".format(testInputClean, expected)
