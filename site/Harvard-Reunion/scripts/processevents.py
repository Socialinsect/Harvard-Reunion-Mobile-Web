#!/usr/bin/env python
"""
Use: processevents.py input_file

Python 2.6 is required.

It automatically generates:
* a filtered version of the CSV with all the stuff we don't care removed.
* a users table CSV
* an events table CSV
* a user_events table CSV

Helpful convention:
  xxx_col = a single Column object
  xxx_cols = a ColumnGroup object (a collection of columns with header names)
"""
import os.path
import string
import sys
from itertools import chain, izip

from datadance import hashhelper
from datadance import ColumnGroup, DataColumn
from datadance.transform import MethodTransform, Transform

def main():
    infile_name = sys.argv[1]
    all_cols = parse_doc(infile_name)
    
    # Extract the cols we care about, sort the event cols by their event ID
    user_cols = select_user_cols(all_cols)
    event_cols = select_event_cols(all_cols).sort_columns()

    # Pull the user cols and event cols together, sort by email (the first col)
    # This is a little ugly because if you change the column order, it changes
    # the sort order. TODO: Have this take args for what fields to sort by.
    sorted_by_email = (user_cols + event_cols).sort_rows()
    
    # Filter out the Voided status rows
    active = sorted_by_email.reject_rows_by_value("status", "Voided")

    # Merge together rows that represent multiple orders from the same person 
    # by looking for orders with the same email address (it must be sorted so
    # that records to be merged are grouped together -- we're sorted by email)
    merged = active.merge_rows(lambda row: row["email"], merge_func=merge_rows)

    # By this point, we've more or less got a clean data file. Now we start to
    # transform it to what we need for our SQL tables.
    merged.write(infile_name + "-filtered.csv")

    # Write our EVENTS table
    events_table = make_events_table(event_cols.column_names)
    events_table.write(infile_name + "-events.csv")

    # Write our USERS table
    users_table = merged.select("user_id", "email", "status", "prefix",
                                "first_name", "last_name", "suffix", 
                                "class_year")
    users_table.write(infile_name + "-users.csv")

    # Write our USERS_EVENTS table
    users_events_table = make_users_events_table(merged)
    users_events_table.write(infile_name + "-users_events.csv")

#################### Parse and Extract ####################

def parse_doc(infile_name):
    with open(infile_name) as infile:
        full_doc = ColumnGroup.from_csv(infile, 
                                        delimiter="\t",
                                        force_unique_col_names=True)
        return full_doc

def select_user_cols(col_grp):
    """Basic user information (each row is actually an order, so we can 
    potentially get the same user buying stuff multiple times)"""
    make_lowercase = MethodTransform(lambda s: s.lower())
    
    # Grab just the email column and transform it so that it's all lowercase
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
        return event_id.strip() + ":" + name.strip()

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
        if key == "user_id":
            new_row[key] = val1
        if key == "class_year":
            new_row[key] = val2
        elif (not val1) or (not val2): # if one is blank, take non-blank...
            new_row[key] = val1 if val1 else val2
        elif val1.isdigit() and val2.isdigit(): # if both are digits, add
            new_row[key] = str(int(val1) + int(val2)) # we only store strings
        else:
            new_row[key] = val2

    return new_row


#################### Write to CSV files ####################

def make_events_table(event_names):
    """The column names are of the format "4324123:Friday Dinner". We want to 
    take all the column names and make a table of event ids and descriptions"""
    return ColumnGroup.from_rows(["event_id", "name"],
                                 (name.split(":") for name in event_names))

def make_users_events_table(all_cols):
    # Grab event columns, but make the column names just the IDs, no description
    event_cols = all_cols.selectf(lambda name: ":" in name,
                                  change_name_func=lambda s: s.split(":")[0])
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
    main()
