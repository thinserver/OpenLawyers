#! /usr/bin/python
# -*- coding: iso-8859-15 -*-

import sys

if len(sys.argv) < 4:
	print "Usage: "+sys.argv[0]+" *.html '<body>' '<BODY>'"
	sys.exit(0)

import os
from fnmatch import fnmatch

def process(filename):
	content = open(filename).read()
	new = content.replace(before, after)
	if content != new:
		open(filename, 'w').write(new)
		print filename+" written."

if len(sys.argv) == 4:
	list_expr = sys.argv[1]
	before = sys.argv[2]
	after = sys.argv[3]

	for filename in os.listdir("."):
		if os.path.isfile(filename) and fnmatch(file, list_expr):
			process(filename)
else:
	before = sys.argv[len(sys.argv)-2]
	after = sys.argv[len(sys.argv)-1]

	for filename in sys.argv[1:len(sys.argv)-2]:
		if os.path.isfile(filename):
			process(filename)

