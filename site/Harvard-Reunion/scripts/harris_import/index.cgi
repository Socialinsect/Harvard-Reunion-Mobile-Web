#!/usr/bin/env python
import cgi
import cgitb
cgitb.enable()

import processevents

def display_form():
    print "Content-Type: text/html"
    print
    print """<!DOCTYPE html>
    <html>
      <head>
        <meta http-equiv="Content-type" content="text/html; charset=utf-8">
        <title>Harris Data Upload</title>
      </head>
      <body>
        <h1>Harris Attendance Data Import</h1>
        <form action="index_submit" method="POST" enctype="multipart/form-data">
          <label for="attendance_file">Upload File:</label>
          <input type="file" name="attendance_file" id="attendance_file"/>
          <p><input type="submit" value="Process &rarr;"></p>
        </form>
      </body>
    </html>"""

def process_form():
    print "Content-Type: text/html"
    print
    print "Hello World!"


if __name__ == '__main__':
    display_form()