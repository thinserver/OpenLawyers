#!/usr/bin/python

from subprocess import Popen, PIPE
from shlex import split

files = Popen(split('find . -name "*.gui"'), stdout=PIPE).communicate()[0].strip().split('\n')

for file in files:
	if file != '':
		new = file.replace('.gui', '.html')
		cmd = 'git mv "'+file+'" "'+new+'"'
		print cmd
		Popen(split(cmd)).wait()