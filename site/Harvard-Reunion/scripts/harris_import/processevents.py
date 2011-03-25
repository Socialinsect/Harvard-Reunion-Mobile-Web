#!/usr/bin/env python
"""
Python 2.6 is required.

It automatically generates:
* a filtered version of the CSV with all the stuff we don't care removed.
* a users table CSV
* an events table CSV
* a user_events table CSV

Helpful convention:
  xxx_col = a single Column object
  xxx_cols = a ColumnGroup object (a collection of columns with header names)
  
Encoding:
* The data created is encoded as Latin-1. We change this to UTF-8 when we write
  out the data file and when we insert values into the database.
"""
import os.path
import sqlite3
import string
import sys
from itertools import izip

from csvcolumns import ColumnGroup
from csvcolumns.transform import MethodTransform

import eventmappings

def main(class_year, infile_name, outfile_base):
    all_cols = parse_doc(infile_name)
    
    # Basic user info like name, graduating year, email
    user_cols = select_user_cols(all_cols)
    # Extract event cols, keep the Event ID, strip Event Name, sort cols by ID
    event_cols = select_event_cols(all_cols).sort_columns()

    # Pull the user cols and event cols together, sort rows by email (the first 
    # col) This is a little ugly because if you change the column order, it 
    # changes the sort order. TODO: Have this take args for what to sort by.
    sorted_by_email = (user_cols + event_cols).sort_rows()

    # Filter out the Voided status rows
    active = sorted_by_email.reject_rows_by_value("status", "Voided")

    # Merge together rows that represent multiple orders from the same person 
    # by looking for orders with the same email address (it must be sorted so
    # that records to be merged are grouped together -- we're sorted by email)
    merged = active.merge_rows(lambda row: row["email"], merge_func=merge_rows)

    # Account for package deals where signing up for one event actually means
    # you're attending multiple ones
    packages_added = add_packages(merged, class_year)

    # By this point, we've more or less got a clean data file. Now we start to
    # transform it to what we need for our SQL tables. The CSV files are just
    # for debugging purposes.
    packages_added.write(outfile_base + "-filtered.csv")

    dbconn = sqlite3.connect(outfile_base + ".db")

    # Write our EVENTS table
    events_table = make_events_table(event_cols.column_names)
    events_table.write(outfile_base + "-events.csv")
    events_table.write_db_table(dbconn, "events", primary_key="event_id")

    # Write our USERS table
    users_table = merged.select("user_id", "email", "status", "prefix",
                                "first_name", "last_name", "suffix", 
                                "class_year")
    users_table.write(outfile_base + "-users.csv")
    users_table.write_db_table(dbconn, "users", primary_key="user_id",
                               indexes=["email", "status", "first_name", "last_name"])

    # Write our USERS_EVENTS table
    users_events_table = make_users_events_table(merged)
    users_events_table.write(outfile_base + "-users_events.csv")
    users_events_table.write_db_table(dbconn, "users_events", 
                                      indexes=["user_id", "event_id", "value"])

    dbconn.close()

#################### Parse and Extract ####################

def parse_doc(infile_name):
    with open(infile_name) as infile:
        full_doc = ColumnGroup.from_csv(infile, 
                                        delimiter="\t",
                                        force_unique_col_names=True,
                                        encoding="latin-1")
        return full_doc

def select_user_cols(col_grp):
    """Basic user information (each row is actually an order, so we can 
    potentially get the same user buying stuff multiple times)"""
    # Grab just the email column and transform it so that it's all lowercase
    make_lowercase = MethodTransform(lambda s: s.lower())
    email_col_grp = make_lowercase(col_grp.select("email"))
    
    # Append all the other columns we care about to the transformed email column
    return email_col_grp + col_grp.select(("order_id", "user_id"),
                                          "status",
                                          ("bill_prefix", "prefix"),
                                          ("bill_first_name", "first_name"),
                                          ("bill_last_name", "last_name"),
                                          ("bill_suffix", "suffix"),
                                          "class_year")

def select_event_cols(col_grp):
    """All events are of the format "Special Dinner #2131230", and correspond to
    line items in the Harris order form. The ColumnGroup we return changes the
    format of the events to look like "2131230:Special Dinner", so that the IDs
    come first."""
    def _reformat_event_name(event_name):
        name, event_id = event_name.split("#")
        return event_id.strip()
        # return event_id.strip() + ":" + name.strip()

    return col_grp.selectf(lambda col_name: "#" in col_name, 
                           change_name_func=_reformat_event_name)

#################### Merge Records ####################

def merge_rows(row1, row2):
    """Returns a tuple that represents our merged row. Merge rules:
    1. If either is blank, use the one that has a value.
    2. Special case: for the user_id, take the first value -- this is likely to
       correspond to their first (and most complete) order.
    3. Special case: for the class_year, just take the last value.
    4. If the two values are numbers, add them
    5. If the two values are strings, the second row wins."""
    new_row = {}
    for key in row1:
        val1, val2 = row1[key], row2[key]
        if (not val1) or (not val2): # if one is blank, take non-blank...
            new_row[key] = val1 if val1 else val2
        elif key == "user_id":
            new_row[key] = val1
        elif key == "class_year":
            new_row[key] = val2
        elif val1.isdigit() and val2.isdigit(): # if both are digits, add
            new_row[key] = str(int(val1) + int(val2)) # we only store strings
        else:
            new_row[key] = val2

    return new_row


#################### Massaging Data ####################

def append_non_harris_event_cols(ids_to_names):
    pass

def add_packages(src_col_grp, class_year):
    """Return a new ColumnGroup that has the row values filled out for all 
    users attending events that are covered by package events. So if package
    event 100 maps to regular events 101, 102hr, 103; then the value for 
    row['100'] should be copied to row['101'], row['102hr'], and row['103']"""
    package_events = eventmappings.mappings_for(class_year)
    
    def _new_row(old_row):
        row = old_row.copy()
        for package_event_id, event_ids in package_events.items():
            for event_id in event_ids:
                row[event_id] = row[package_event_id]
        return row
    
    return src_col_grp.transform_rows(_new_row)
    

#################### Write to CSV files ####################

def make_events_table(event_names):
    """The column names are of the format "4324123:Friday Dinner". We want to 
    take all the column names and make a table of event ids and descriptions"""
    return ColumnGroup.from_rows(["event_id", "name"],
                                 (name.split(":") for name in event_names))

def make_users_events_table(all_cols):
    # Grab event columns, but make the column names just the IDs, no description
    event_cols = all_cols.selectf(lambda name: name.isdigit())
    rows = iterate_user_events(all_cols.user_id, event_cols)
    return ColumnGroup.from_rows(["user_id", "event_id", "value"], rows)

def iterate_user_events(user_id_col, event_cols):
    """Returns an iterable of (user_id, event_id, event_value) tuples.
    event_values can be numbers or freeform text -- it's user input driven."""
    event_ids = event_cols.column_names
    for user_id, event_row in izip(user_id_col, event_cols.iter_rows()):
        # Convert event_row to be a sequence of (event_id, event_value) tuples
        event_ids_values = zip(event_ids, event_row)
        events_for_user = [(event_id, event_value)
                           for (event_id, event_value) in event_ids_values
                           if event_value]
        for event_id, event_value in events_for_user:
            yield (user_id, event_id, event_value)


if __name__ == '__main__':
    # We're passing in class_year explicitly instead of deriving it from the 
    # data file because of cases like Harvard and Radcliffe differntiating their
    # Harvard and Radcliffe grads (like 1961R), which doesn't come through in 
    # the Harris feed.
    if len(sys.argv) != 4:
        print "Usage: processevents.py [class_year] [Harris input file] " \
              "[output file base]\n" \
              "  Example: processevents.py harris25th.txt 1975_35th\n\n" \
              "  Will create: 1975_35th.db, 1975_35th-events.csv, etc."

    class_year = sys.argv[1]
    infile_name = sys.argv[2]
    outfile_base = sys.argv[3]

    main(class_year, infile_name, outfile_base)
