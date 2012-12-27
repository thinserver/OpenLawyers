#!/usr/bin/python

from subprocess import Popen, PIPE
from shlex import split

for e in ["*.gui", "*.html", "*.php"]:
	files = Popen(split('find . -name "'+e+'"'), stdout=PIPE).communicate()[0].strip().split('\n')

	for file in files:
		if file != '':
			# edit content
			content = open(file).read()
			if '.gui' in content:
				content = content.replace('.gui', '.html')
				open(file, 'w').write(content)
				print file+' changed.'

		if '.gui' in file:
			# rename file
			new = file.replace('.gui', '.html')
			cmd = 'git mv "'+file+'" "'+new+'"'
			print cmd
			Popen(split(cmd)).wait()
			cmd = 'mv "'+file+'" "'+new+'"'
			Popen(split(cmd)).wait()

