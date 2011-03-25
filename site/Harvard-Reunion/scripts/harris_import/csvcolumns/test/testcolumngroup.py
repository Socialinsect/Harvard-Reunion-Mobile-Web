"""
testcolumns.py
"""
import unittest
import sys
import os

from datadance.columngroup import ColumnGroup
from datadance.column import DataColumn

class TestColumnGroup(unittest.TestCase):
    
    def setUp(self):
        self.first_name_col = DataColumn(["Stefie", "Piotr"])
        self.last_name_col = DataColumn(["Tellex", "Mitros"])
        self.language_col = DataColumn(["Ruby", "C"])
        self.coding_friends = ColumnGroup([("FirstName", self.first_name_col),
                                           ("LastName" , self.last_name_col),
                                           ("Language" , self.language_col)])
    
    def test_duplicate_col_name(self):
        """Don't allow duplicate column names"""
        c1 = DataColumn(["one", "two"])
        
        # This should be fine...
        cg = ColumnGroup([("name1",c1), ("name 2", c1)]) 

        # This should blow up because the names are not unique
        self.assertRaises( ValueError, 
                           lambda: ColumnGroup([("name1",c1), ("name1", c1)]) )

    def test_parse_csv(self):
        """Check normal CSV parsing with spaces"""
        
        csv_text = """FirstName, LastName, FavoriteColor
        Regina, Eum,Purple 
        David, Ormsbee,Blue """
        
        cg = ColumnGroup.from_csv(csv_text)
        self.assertEqual(cg.FirstName, cg[0])
        self.assertEqual(cg.LastName, cg[1])
        self.assertEqual(cg.FavoriteColor, cg[2])
        
        self.assertEqual(cg.column_names, 
                         ('FirstName', 'LastName', 'FavoriteColor'))

        self.assertEqual(cg.FirstName,
                         DataColumn(["Regina", "David"]) )
        self.assertEqual(cg.LastName,
                         DataColumn(["Eum", "Ormsbee"]) )
        self.assertEqual(cg.FavoriteColor,
                         DataColumn(["Purple", "Blue"]) )
    
    def test_select(self):
        """Test creating a ColumnGroup by selecting columns"""
        cg = self.coding_friends.select("Language", "FirstName")
        self.assertEqual(cg, ColumnGroup([("Language", self.language_col),
                                          ("FirstName", self.first_name_col)]) )
        cg2 = self.coding_friends.select("Language", ("FirstName", "first"))
        self.assertEqual(cg2, ColumnGroup([("Language", self.language_col),
                                           ("first", self.first_name_col)]) )

#    def test_selectf(self):
#        """Test creating a ColumnGroup by rejecting a columns based on a 
#        function."""
#        cg = self.coding_friends.select(lambda c: c.startswith('L'))
#        self.assertEqual(cg, ColumnGroup([("LastName" , self.last_name_col),
#                                          ("Language" , self.language_col)]))
                                           
    def test_sorted(self):
        """Test that we can create a ColumnGroup with columns sorted by column name."""
        cg = self.coding_friends.sorted()
        self.assertEqual(cg, ColumnGroup([("FirstName", self.first_name_col),
                                          ("Language" , self.language_col),
                                          ("LastName" , self.last_name_col)]))
        # But it shouldn't have changed the original
        self.assertEqual(self.coding_friends,
                         ColumnGroup([("FirstName", self.first_name_col),
                                      ("LastName" , self.last_name_col),
                                      ("Language" , self.language_col)]) )

    def test_map_col_names(self):
        """Test that creating a new CG and mapping the column names works."""
        cg = self.coding_friends.map_names({ "FirstName" : "first", "LastName" : "last"})
        self.assertEqual(cg, ColumnGroup([("first", self.first_name_col),
                                          ("last", self.last_name_col),
                                          ("Language" , self.language_col)]) )

    def test_reject(self):
        """Test creating a ColumnGroup by rejecting columns"""
        cg = self.coding_friends.reject("Language", "LastName", "Asparagus")
        self.assertEqual(cg, ColumnGroup([("FirstName", self.first_name_col)]) )

#    def test_rejectf(self):
#        """Test creating a ColumnGroup by rejecting a columns based on a 
#        function."""
#        pass
        
        
    def test_non_strings(self):
        """Test ColumnGroups with non-string values"""
        num_col = DataColumn([100, 200, 300, 400])
        bool_col = DataColumn([True, False, True, True])
        none_col = DataColumn(["", None, None, ""])

        names = ["numbers", "booleans", "nones"  ]
        cols  = [ num_col ,  bool_col ,  none_col]
        cg = ColumnGroup( zip(names, cols) )
        print cg.to_csv()
        
    def test_empty(self):
        """Test that empty ColumnGroups are ok"""
        cg = ColumnGroup(()) # empty column group
        self.assertFalse(cg)
        print cg
        
    def test_adding_col_groups(self):
        """Test that we can create new ColumnGroups by adding old ones"""
        cg1 = ColumnGroup( zip(["firstName", "lastName"],
                               [self.first_name_col, self.last_name_col]))
        cg2 = ColumnGroup( [("language", self.language_col)] )
        cg3 = cg1 + cg2
        self.assertEqual(cg3.column_names, ("firstName", "lastName", "language"))
        self.assertEqual(cg3.columns, (self.first_name_col, self.last_name_col,
                                       self.language_col))