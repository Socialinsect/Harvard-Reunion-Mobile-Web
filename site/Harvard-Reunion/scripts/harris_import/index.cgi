#!/usr/bin/env python2.6
import cgi
import cgitb
import io
import os.path
from StringIO import StringIO
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
        <form action="index.cgi" method="POST" enctype="multipart/form-data">
          <label for="reunion_year">Reunion Year:</label>
          %s
          <br/>
          <br/>
          <label for="attendance_file">Upload File:</label>
          <input type="file" name="attendance_file" id="attendance_file"/>
          <p><input type="submit" value="Process &rarr;"></p>
        </form>
      </body>
    </html>""" % year_dropdown()

def year_dropdown():
    YEARS = ['2006', '2001', '1996', '1991', '1986', '1976', '1961', '1956', 
             '1951', '1946']
    options_html = '\n\t'.join(['<option value="%s">%s</option>' % (year, year) 
                               for year in YEARS])
    html = '<select name="reunion_year" id="reunion_year">%s</select>' % options_html
    return html
    

def response_start():
    print "Content-Type: text/html"
    print
    print "File Uploaded! <a href='index.cgi'>Upload another</a>"


if __name__ == '__main__':
    form = cgi.FieldStorage()
    if "attendance_file" in form:
        response_start()

        year = form["reunion_year"].value
        if not year.isdigit():
            raise ValueError("Invalid year: %s" % year)
        
        contents = form["attendance_file"].file.read()
        infile = io.StringIO(contents, newline=None)
        db_path = os.path.normpath(
                      os.path.join(__file__,
                                   "../../../data/schedule/",
                                   "%s.db" % year)
                  )
        
        processevents.main(year, infile, db_path)
    else:
        display_form()