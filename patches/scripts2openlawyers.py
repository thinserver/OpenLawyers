#!/usr/bin/python

from subprocess import Popen, PIPE
from shlex import split

files = Popen(split('find . -name "*.html"'), stdout=PIPE).communicate()[0].strip().split('\n')

for file in files:
	if file != '':
		content = open(file).read()
		if 'scripts.js' in content:
			content = content.replace('scripts.js', 'openlawyers.js')
			open(file, 'w').write(content)
			print file+' changed.'
