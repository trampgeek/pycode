ABOUT PYCODE
============

@version 26 May 2012
@author Richard Lobb, University of Canterbury, New Zealand.

pycode is a Moodle question type that requests students to submit python code to some given specification, e.g. a function sqr(x) that returns its parameter squared. The submission is graded by running a series of testcases of the code in a sandbox, comparing the output with the expected output. If all testcases pass, the question is deemed correct, otherwise it is incorrect. pycode is expected to be run in a special adaptive mode, so the student submits each question one by one repeatedly, until a correct result is obtained. Their mark for the question is then determined by the number of submissions and the per-submission penalty set within Moodle in the usual way.

pycode requires a Moodle version >= 2.1 and provides quiz questions in Python 2.7.

There are two steps involved in installing the pycode module: first install the pypy sandbox then install the Moodle pycode question type plug-in and its associated behaviour module.

A. Install pypy

pycode uses the pypy standbox (see pypy.org) to securely run the submitted python programs. To avoid problems with the constant stream of pypy updates, and to add a little bit of my own code as a front end, I've forked the base pypy distribution; my fork is available as a mercurial distribution at  https://bitbucket.org/trampgeek/pypy-sandbox-4-pycode/wiki/Home. Unfortunately, this has to be built from source. The steps are:

1. As superuser, clone the source from the Mercurial repository into a directory /usr/local/pypy-sandbox-4-pycode by, for example:

    cd /usr/local
    sudo hg clone https://bitbucket.org/trampgeek/pypy-sandbox-4-pycode

2. Install all the dependencies as per http://pypy.readthedocs.org/en/latest/getting-started-python.html#translating-the-pypy-python-interpreter

3. Build the sandbox by:

        cd /usr/local/pypy-sandbox-4-pycode/pypy/translator/goal
        python translate.py -O2 --sandbox

 [After at least half an hour and possibly several hours ...]

        mv pypy-c /usr/local/pypy-sandbox-4-pycode/pypy/translator/sandbox/pypy-sandbox

Ensure the whole pypy-sandbox-4-pycode subtree is readable and (where appropriate) executable by the web server.

B. Install pycode

1.  A special Moodle quiz question behaviour is required for pycode, called adaptive_adapted_for_pycode. [It's adapted for progcode rather than pycode to accommodate other subclasses of progcode, such as ccode]. It's available as a GIT repo. Install it by:

        git clone https://github.com/trampgeek/adaptive_adapted_for_progcode
        sudo mv adaptive_adapted_for_progcode/ <moodle_base>/question/behaviour/

2.  Install the pycode quiz question plug-in itself by:

        git clone https://github.com/trampgeek/pycode
        sudo mv pycode <moodle_base>/question/type/

3. Test it in Moodle by:

   * Logging in as an administrator
   * You should be told there are modules available to update.
   * Update them.
   * Select Settings > Site administrations > Development > Unit tests
   * Run the tests in folder question/type/pycode
   * You shouldn't get any errors. If you do, you're in trouble. Panic.

An issue with SELinux
---------------------

One major issue should be mentioned: if you're running SElinux, there's an issue with running Python from the webserver (which is necessary to provide the front-end to pypy). It's a known Python/SELinux issue (e.g. see
http://stackoverflow.com/questions/3762566/occasional-ctypes-error-importing-numpy-from-mod-wsgi-django-app or https://bugzilla.redhat.com/show_bug.cgi?id=582009). An SELinux expert is apparently able to solve the problem by first turning on logging of SELinux AVC denials then adding a specific exception to allow the offending operation. However, no-one here is clued up enough to do that -- we just turn off SELinux, but you may not wish to do that. In which case, you're on your own!
