from datadance.column import DataColumn
from datadance.transform import *

def simple_trans_method(s):
    return s.lower()

def test_simple_transform():
    """Make sure our basic Transforms run without erroring."""
    d1 = DataColumn(["a", "b", "c"])
    d2 = DataColumn(["A", "B", "C"])
    
    t = MethodTransform(lambda x: x.upper())
    lazy = t(d1)
    assert(lazy.value_hash == d2.value_hash)
    
    print str(lazy)
    
    t2 = MethodTransform(simple_trans_method)
    d3 = t2(d1)
    print repr(d3)
    print d3._transformed_col

    print d3
    
    #assert(True is False)