"""
testcolumns.py

Created by David Ormsbee on 2008-08-01.
Copyright (c) 2008 __MyCompanyName__. All rights reserved.
"""
import unittest
import sys
import os

from datadance.column import DataColumn
from datadance.transform import MappingTransform

class TestDataColumn(unittest.TestCase):
    
    def setUp(self):
        self.towns_col = DataColumn(["Nanakuli", "Waianae", "Waipahu"])
    
    def test_simple_iteration(self):
        """Plain iterator through all the values in the column"""
        d_iter = iter(self.towns_col)
        self.assertEqual(d_iter.next(), "Nanakuli")
        self.assertEqual(d_iter.next(), "Waianae")
        self.assertEqual(d_iter.next(), "Waipahu")
    
    def test_numbered_iteration(self):
        """Iteration with row nums"""
        # This should iterate and return the (row num, value) pairs like enumerate
        d_num_iter = self.towns_col.iter_rows(0)
        d_enum_iter = enumerate(self.towns_col)
        self.assertEqual(d_num_iter.next(), d_enum_iter.next())
        self.assertEqual(d_num_iter.next(), d_enum_iter.next())
        self.assertEqual(d_num_iter.next(), d_enum_iter.next())
        
        # This should start at one (default start value)
        d_num_iter = self.towns_col.iter_rows()
        self.assertEqual(d_num_iter.next(), (1, "Nanakuli") )
        self.assertEqual(d_num_iter.next(), (2, "Waianae") )
        self.assertEqual(d_num_iter.next(), (3, "Waipahu") )

        # This should iterate and return the (row num, value) pairs, but starts
        # at 5 instead of the default 1
        d_num_iter = self.towns_col.iter_rows(count_from=5)
        self.assertEqual(d_num_iter.next(), (5, "Nanakuli") )
        self.assertEqual(d_num_iter.next(), (6, "Waianae") )
        self.assertEqual(d_num_iter.next(), (7, "Waipahu") )

    def test_access(self):
        """Test access to a Column's underlying row data."""
        self.assertEqual(self.towns_col[0], "Nanakuli")
        self.assertEqual(self.towns_col[1], "Waianae")
        self.assertEqual(self.towns_col[2], "Waipahu")

        def modify_column():
            """Should throw TypeError - a Column is meant to be immutable."""
            col = DataColumn(["Rob", "Kim"])
            col[0] = "Marissa"

        d1 = ["Coleman", "Wielgosz", "Goodman"]
        c1 = DataColumn(d1)
        
        self.assertEqual(d1[1], c1[1])
        self.assertEqual(len(d1), len(c1))
        self.assertRaises(TypeError, modify_column)

    def test_delayed_hash(self):
        d1 = DataColumn(["a", "b", "c"])
        self.assertEqual(d1._value_hash, None)
        d2 = d1
        self.assertEqual(d1._value_hash, None)
        self.assertTrue(d1 == d2)
        self.assertEqual(d1._value_hash, None)
        print d1
        self.assertEqual(d1._value_hash, None)
        print d1.identity_hash
        self.assertNotEqual(d1._value_hash, None)
        

    def test_hash(self):
        """Basic tests for Column hashing"""
        d1 = ["Joe", "Dibin", "Ravi", "Jim", "Eli"]
        d2 = ["Joe", "Dibin", "Ravi", "Jim", "Eli"] # Identical to d1
        d3 = ["Joe", "Dibin", "Jim", "Ravi", "Eli"] # Different ordering
        d4 = ["JoeD", "ibin", "Jim", "Ravi", "Eli"] # One letter shifted
        d5 = ["Joe", "Dibin", "Jim"] # Fewer elements
        
        c1, c2, c3, c4, c5 = ( DataColumn(x) for x in (d1, d2, d3, d4, d5) )
        # This is the Python internal version of the hash (32-bit)
        h1, h2, h3, h4, h5 = ( hash(c) for c in (c1, c2, c3, c4, c5) )
        # This is our computed sha1 hash...
        m1, m2, m3, m4, m5 = ( c.identity_hash for c in (c1, c2, c3, c4, c5))
        
        self.assertEqual(h1, h2, 
                         "Cols formed with same strings should have same hash.")
        self.assertEqual(m1, m2, 
                         "Cols formed with same strings should have same hash.")
        self.assertNotEqual(h1, h3, "Ordering should affect hashes.")
        self.assertNotEqual(m1, m3, "Ordering should affect hashes.")
        self.assertNotEqual(h1, h4, 
                            "Minor edits/shifts in strings should alter hash.")
        self.assertNotEqual(m1, m4, 
                            "Minor edits/shifts in strings should alter hash.")
        self.assertNotEqual(h1, h5, "Missing elements should alter hash.")
        self.assertNotEqual(m1, m5, "Missing elements should alter hash.")
        
    def test_explicit_hash(self):
        """Check to make sure we don't overwrite the hash if it's set explicitly."""
        c1 = DataColumn(["Apples", "Oranges"], explicit_hash="lamehash")
        c2 = DataColumn(["Banana", "Pineapple"], explicit_hash="lamehash")
        self.assertEqual(c1, c2, 
                        "Should not be checking contents if explicit_hash was set")

    def test_unique(self):
        """Check that unique values work."""
        animals = DataColumn(["Dog", "dog", "dog", "cat"])
        self.assertEqual(len(animals.unique_values), 3)

    def test_non_strings(self):
        """Test non string types"""
        num_col = DataColumn([100, 200, 300, 400])
        bool_col = DataColumn([True, False, True, True])
        none_col = DataColumn(["", None, None, ""])

    def test_ancestors(self):
        """Test that we can crawl our ancestor tree properly"""
        d = DataColumn(["a", "b", "c"])
        t1 = MappingTransform({"a" : "foo"})
        t2 = MappingTransform({"b" : "bar"})
        t3 = MappingTransform({"c" : "aloha"})
        
        # d --> z1 --> z2 ---> z3
        z1 = t1(d)
        z2 = t2(z1)
        z3 = t3(z2)
        self.assertEqual(z3.ancestors, [z2, z1, d])
        self.assertEqual(z3.original_col, d)
        
        # d --> z1 --> z4
        z4 = t3(z1)
        self.assertEqual(z4.ancestors, [z1, d])
        self.assertEqual(z4.original_col, d)

    def test_stages(self):
        d = DataColumn(["a", "b", "c"])
        t1 = MappingTransform({"a" : "foo"})
        t2 = MappingTransform({"b" : "bar"})
        t3 = MappingTransform({"c" : "aloha"})

        z1 = t1(d, "foo substitution")
        z2 = t2(z1, "bar substitution")
        z3 = t3(z2, "aloha substitution")
        
        self.assertEqual(z3.history("foo substitution"), z1)
        self.assertEqual(z3.history("bar substitution"), z2)
        self.assertEqual(z3.history("aloha substitution"), z3)

    def test_single_element(self):
        d = DataColumn(["test"])
        s = str(d)
        self.assertEqual(s, "test")
        
#class TestLazyColumn(unittest.TestCase):
#    
#    def test_proxy(self):
#        """Test that the LazyColumn proxies method calls properly"""
#        dogs = DataColumn(["Rusty", "Patty", "Jack", "Clyde"])
#        lazy_dogs = LazyColumn(dogs, lambda x: x, 
#                               transform_method_hash="Bad Hash")
#        #print lazy_dogs
#        #self.assertEqual(dogs[0], lazy_dogs[0])
        

if __name__ == '__main__':
    unittest.main()

