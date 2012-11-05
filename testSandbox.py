# Simple check on the pypy sandbox set up for running the Moodle 2 pycode tests.

import base64
import json
import os
import marshal
import subprocess

testSpec = ["def blah():\n   return 'a\\nb\\n'",
    [["print(blah())", "a\nb"]]
]
#studentCode = "def blah():\n   return 'a\\nb\\n'"
#expression = "print(blah())"
#result = "a\nb"
#testSetEncoded = json.dumps([studentCode, [(expression, result)]])
testSpecEncoded = base64.b64encode(json.dumps(testSpec))  # Will need to convert to ASCII first in Python 3
print testSpecEncoded

SANDBOX = "/usr/local/sandbox/python3/pycodeTest.py"
try:
    subproc = subprocess.Popen([SANDBOX, testSpecEncoded], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    (result, stderr) = subproc.communicate()
    if stderr != '':
       print "Stderr:", stderr
    lines = result.split('\n')[:-1]
    for i in range(0, len(lines) - 1, 2):
        print lines[i]
        print lines[i+1]
except subprocess.CalledProcessError, e:
    print "*** Error on invoking sandbox: ", e

