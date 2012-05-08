#!/usr/bin/env python2.6
import cgi
import cgitb
import io
import os.path
from StringIO import StringIO
cgitb.enable()

import processevents


YEARS = ['2007', '2002', '1997', '1992', '1987', '1977', '1962', 'H1957', 'R1957', 'H1952', 'R1952', 'H1947', 'R1947']

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
    options_html = '\n\t'.join(['<option value="%s">%s</option>' % (year, year) 
                               for year in YEARS])
    html = '<select name="reunion_year" id="reunion_year">%s</select>' % options_html
    return html
    

def response_start():
    print "Content-Type: text/html"
    print
    print "<html><head><title>Upload Results</title></head>"


if __name__ == '__main__':
    form = cgi.FieldStorage()
    if "attendance_file" in form:
        response_start()

        year = form["reunion_year"].value
        if year not in YEARS:
            raise ValueError("Invalid year: %s" % year)
        
        contents = form["attendance_file"].file.read()
        infile = io.BytesIO(contents)
        db_path = os.path.normpath(
                      os.path.join(__file__,
                                   "../../../data/schedule/",
                                   "%s.db" % year)
                  )
        
        merge_log = processevents.main(year, infile, db_path)

        print "<body>"
        print "<p>File Uploaded! <a href='index.cgi'>Upload another</a></p>"
        
        if merge_log:
            print "<p>Warning: Records were merged</p>"
            print "<pre>"
            print processevents.format_merge_log(merge_log)
            print "</pre>"
        
        print "</body>"
        print "</html>"
        
    else:
        display_form()
