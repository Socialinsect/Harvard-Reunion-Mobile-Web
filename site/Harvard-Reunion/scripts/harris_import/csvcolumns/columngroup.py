"""
* ColumnGroups are an ordered, named grouping of Data/LazyColumns.  Column
  names must be unique.  ColumnGroups can be created from CSVs.
"""
import csv
from collections import namedtuple
from cStringIO import StringIO
from itertools import count, groupby, imap, izip

from column import DataColumn


class ColumnGroup(object):
    """A ColumnGroup is a way to group columns together and give them names.
    A Column object is just data, it has no name or identifier.  This is a
    helpful abstraction because many times the same column data is referenced
    under many different names.

    Column names must be unique.
    """

    def __init__(self, name_col_pairs):
        """
        @param name_col_pairs is an iterable of (string, Column) tuples, where
                              the string is the name of the Column.  Column
                              names must be unique.  Column lengths must be
                              the same.  An empty list is acceptable.
        """
        # An empty name_col_pairs means an empty ColumnGroup, but that might
        # still be valid.
        if not name_col_pairs:
            self._name_to_col = {}
            self._column_names = ()
            self._columns = ()
            return

        # Check: Our name/column pairs really are pairs
        for pair in name_col_pairs:
            if len(pair) != 2:
                raise ValueError("Pair %s does not have 2 elements" %
                                 (repr(pair)))
            
        self._name_to_col = dict(name_col_pairs)
        self._column_names, self._columns = zip(*name_col_pairs)

        # Check: Column names are unique
        if len(name_col_pairs) != len(self._name_to_col):
            raise ValueError("Column names must be unique.")

        # Check: All columns have the same length
        column_lengths = [ len(col) for col in self.columns ]
        if len(frozenset(column_lengths)) > 1: # 0 is ok -> empty ColumnGroup
            raise ValueError("Columns must have the same length: %s" % \
                              zip(self.column_names, column_lengths))

        self._col_name_to_index = dict(zip(self._column_names,
                                           range(self.num_cols)))

        # self.Row = namedtuple('Row', self.column_names, rename=True)

    ###################### Query ###################### 
    @property
    def column_names(self):
        """Return column names, in the order they appear in the
        ColumnGroup."""
        return self._column_names
    
    @property
    def columns(self):
        """Return the column objects, in the order they appear in the
         ColumnGroup."""
        return self._columns
    
    @property
    def num_cols(self):
        """Return the number of columns."""
        return len(self.columns)
        
    @property
    def num_rows(self):
        """Return the number of rows in the columns, or 0 if there are no
        columns."""
        if self.num_cols > 0:
            return len(self.columns[0])
        else:
            return 0

    @property
    def rows(self):
        return zip(*self.columns)
        
    @property
    def dict_rows(self):
        return [dict(zip.self.column_names, row) for row in self.iter_rows()]

    def sort_rows(self):
        """Return a ColumnGroup with the same columns as this one, but where
        the rows have been sorted (sorts look at first column, then second, then
        third, etc...)"""
        return ColumnGroup.from_rows(self.column_names, sorted(self.rows))

    def reject_rows_by_value(self, column_name, reject_value):
        col = self[column_name]
        new_rows = (row for i, row in enumerate(self.rows) 
                    if col[i] != reject_value)
        return ColumnGroup.from_rows(self.column_names, new_rows)

    def row_from_dict(self, d):
        self._col_name_to_index
        
        row = [None for i in range(self.num_cols)]
        for col_name, val in d.items():
            row[self._col_name_to_index[col_name]] = val

        return tuple(row)

    def merge_rows(self, select_func, merge_func):
        """Return a new ColumnGroup which has merged rows from this ColumnGroup,
        with the following rules:
            
        1. Rows to merge must be consecutive
        2. select_func and merge_func should take dictionaries for their row
           arguments (representing row1, and row2)
        3. If two rows need to be merged, select_func(row1, row2) returns True
        4. merge_func should return a dictionary that is the result of merging
           row1 and row2
        """
        rows = []
        for key, row_grp in groupby(self.iter_dict_rows(), select_func):
            row_grp_tpl = tuple(row_grp)
            if len(row_grp_tpl) == 1:
                row = self.row_from_dict(row_grp_tpl[0])
            if len(row_grp_tpl) > 1:
                row = self.row_from_dict(reduce(merge_func, row_grp_tpl))
            rows.append(row)
        
        return ColumnGroup.from_rows(self.column_names, rows)
        
    
    ###################### Built-ins and Iterations ###################### 
    def __add__(self, other):
        return ColumnGroup( zip(self.column_names + other.column_names,
                                self.columns + other.columns) )

    def __contains__(self, item):
        return (item in self._name_to_col) or \
               (item in self._name_to_col.values())
    
    def __eq__(self, other):
        return (self.column_names == other.column_names) and \
               (self.columns == other.columns)
    
    def __getattr__(self, name):
        try:
            return self._name_to_col[name]
        except KeyError:
            raise AttributeError(name)
    
    def __getitem__(self, index):
        """Get column at this index if it's a number, or this column name if
        it's a String."""
        # print sorted(self._name_to_col.keys())
        if isinstance(index, int):
            return self._columns[index]
        elif isinstance(index, basestring):
            return self._name_to_col[index]
            
    def __iter__(self):
        """Return name/column iteration"""
        return izip(self.column_names, self.columns)
        
#    def iter_named_rows(self):
#        for row_tuple in izip(*self.columns):
#            yield self.Row(row_tuple)

    def iter_rows(self):
        return izip(*self.columns)
        
    def iter_dict_rows(self):
        for row in self.iter_rows():
            yield dict(zip(self.column_names, row))

    def __len__(self):
        return self.num_cols

    def __repr__(self):
        return "ColumnGroup(\n%s\n)" % \
               ",\n".join([ "  " + repr(name_col_pair) 
                            for name_col_pair in self ])
    def __str__(self):
        return self.to_csv()
    
    ###################### Selectors ######################
    def map_names(self, old_to_new_names):
        def _gen_remapped_pair(old_name):
            if old_name in old_to_new_names:
                return (old_to_new_names[old_name], self[old_name])
            else:
                return (old_name, self[old_name])
        
        return ColumnGroup([ _gen_remapped_pair(col_name) 
                             for col_name in self.column_names ])

    def reject(self, *col_names):
        """Return a new ColumnGroup based on this one, with certain names
        rejected.  Since we're usually looking to just discard optional fields
        and the like, there is no exception thrown if you try to reject a 
        column name that does not exist in the ColumnGroup."""
        names_to_reject = frozenset(col_names)
        return ColumnGroup([ (col_name, self[col_name]) 
                             for col_name in self.column_names
                             if col_name not in names_to_reject ])

    def rejectf(self, reject_func):
        return ColumnGroup([ (col_name, self[col_name]) 
                              for col_name in self.column_names
                              if not rejectf(col_name)])


    def select(self, *col_names):
        """
        Return a new ColumnGroup based on this ColumnGroup, but only selecting
        certain columns.  This can also be used to create a new ColumnGroup
        with the columns in a different ordering.
        """
        def _gen_name_col_pair(name):
            """Takes a name, which is either:
                 * a string indicating the column name or...
                 * a pair of strings indicating (old_col_name, new_col_name),
                   so that you can rename the columns you're selecting on.
            """
            if isinstance(name, basestring):
                return (name, self[name])
            elif isinstance(name, tuple) and (len(name) == 2):
                old_name, new_name = name
                return (new_name, self[old_name])
            else:
                raise ValueError("Selection is only supported by name " \
                                 "or (old_name, new_name) tuples.")
        
        return ColumnGroup([ _gen_name_col_pair(col_name)
                             for col_name in col_names ])

    def selectf(self, select_func, change_name_func=lambda x: x):
        """
        Return a new ColumnGroup based on this ColumnGroup, but only selecting
        columns for which select_func(column_name) returns True. If 
        change_name_func is defined, we will rename the columns using it.
        """
        return ColumnGroup([(change_name_func(col_name), col)
                           for col_name, col in self
                           if select_func(col_name)])


    def split(self, *col_names):
        return self.select(*col_names), self.reject(*col_names)

    def sort_columns(self):
        return self.select(*sorted(self.column_names))

    ###################### IO ###################### 
    # FIXME:
    #    * can use chardet to guess the encoding
    
    @classmethod
    def from_rows(cls, column_names, rows):
        # force it to a list so that we can tell if it's empty (generators will
        # return true even if they're empty). Memory inefficient kludge, but we
        # don't care about memory as much any longer. :-/
        rows = list(rows)
                          
        if not rows: # no rows because it's None or an empty list (just a header)
            return cls([(cn, DataColumn.EMPTY) for cn in column_names])
        else:
            cols = [DataColumn(col) for col in zip(*rows)]
            return cls(zip(column_names, cols))
    
    @classmethod
    def from_csv(cls, csv_input, strip_spaces=True, skip_blank_lines=True,
                 encoding="utf-8", delimiter=",", force_unique_col_names=False):
        """
        Both strip_spaces and skip_blank_lines default to True because these
        things often happen in CSV files that are exported from Excel.
        """
        def _force_unique(col_headers):
            seen_names = set()
            unique_col_headers = list()
            for i, col_name in enumerate(col_headers):
                if col_name in seen_names:
                    col_name += "_%s" % i
                seen_names.add(col_name)
                unique_col_headers.append(col_name)
            return unique_col_headers

        def _pad_row(row):
            if len(row) < num_cols:
                for i in range(num_cols - len(row)):
                    row.append('')
            return row

        def _process_row(row):
            if strip_spaces:
                return _pad_row( [value.strip() for value in row] )
            else:
                return _pad_row( row )

        if isinstance(csv_input, basestring):
            csv_stream = StringIO(csv_input)
        else:
            csv_stream = csv_input
        
        csv_reader = csv.reader(csv_stream, delimiter=delimiter)
            
        column_headers = [header.strip() for header in csv_reader.next()]
        if force_unique_col_names:
            column_headers = _force_unique(column_headers)
        num_cols = len(column_headers)

        # Make a list to gather entries for each column in the data file...
        raw_text_cols = [list() for i in range(num_cols)]
        for row in csv_reader:
            processed_row = _process_row(row)
            # Add this new row if we either allow blank lines or if any field
            # in the line is not blank. We do this to the processed row,
            # because spaces may or may not be significant, depending on
            # whether strip_spaces is True.
            if (not skip_blank_lines) or any(processed_row):
                for i in range(num_cols):
                    raw_text_cols[i].append(unicode(processed_row[i], encoding))

        # Now take the raw data and put it into our DataColumn...
        cols = [ DataColumn(raw_col) for raw_col in raw_text_cols ]

        # column_headers ex: ['FirstName', 'LastName']
        # cols ex: 
        #   [ DataColumn(["David", "John"]), DataColumn(["Ormsbee", "Doe"]) ]
        return ColumnGroup(zip(column_headers, cols))


    def write(self, filename, show_row_nums=False, encoding="utf-8"):
        with open(filename, "wb") as outfile:
            self.to_csv(outfile, show_row_nums=show_row_nums, encoding=encoding)

    def write_db_table(self, conn, table_name, primary_key=None, indexes=tuple()):
        def _iter_cols_sql():
            for col_name in self.column_names:
                if col_name == primary_key:
                    yield "%s integer primary key" % col_name
                else:
                    yield "%s varchar" % col_name

        cur = conn.cursor()

        # create our table (get rid of the old one if it exists)
        drop_table_sql = "drop table if exists %s" % table_name
        create_table_sql = "create table %s(%s)" % \
                           (table_name, ", ".join(_iter_cols_sql()))

        cur.execute(drop_table_sql)
        cur.execute(create_table_sql)

        # Now our indexes...
        for index_col in indexes:
            index_name = "%s_%s_idx" % (table_name, index_col)
            cur.execute("drop index if exists %s" % index_name)
            cur.execute("create index %s on %s(%s)" % \
                        (index_name, table_name, index_col))

        # Now add our data
        if self.num_rows > 0:
            col_placeholders = ",".join(list("?" * self.num_cols))
            insert_sql = u"insert into %s values (%s)" % \
                         (table_name, col_placeholders)
            cur.executemany(insert_sql, self.iter_rows())

        conn.commit()
        cur.close()
        

    def to_csv(self, stream=None, show_row_nums=False, encoding="utf-8"):
        # We have the column data, now we need to write it out as a CSV
        if stream:
            stream_passed_in = True
        else:
            stream_passed_in = False
            stream = StringIO()

        def _encode(s):
            return s.encode(encoding)

        writer = csv.writer(stream) # use csv lib to do our string escaping

        # Convert to bytes (str) for writing...
        encoded_col_names = map(_encode, self.column_names)
        encoded_rows = (map(_encode, row) for row in self.iter_rows())
        if show_row_nums:
            writer.writerow(['rowNum'] + encoded_col_names)
            writer.writerows([str(i)] + row 
                             for i, row in enumerate(encoded_rows, start=1))
        else:
            writer.writerow(encoded_col_names)
            writer.writerows(encoded_rows)
        
        if not stream_passed_in:
            return stream.getvalue()
