"""
transform.py

Transforms

FIXME: We have Transform exceptions, use them.

"""
import hashhelper
from column import Column, DataColumn, LazyColumn
from columngroup import ColumnGroup

class TransformInitError(Exception):
    """A TransformInitError is raised if there's a problem in initializing the 
    transform.  This could be because the arguments are of the wrong type, or 
    because they would cause ambiguity in the transform.  It should only be
    thrown when Transforms are created, not when they're run."""
    def __init__(self, transform, message):
        self.transform = transform
        self.message = message


class Transform(object):
    """
    A Transform is an operation that converts one column into another.
    * Applying a transform on a column returns a new column, and does not affect
      values in the old one.  This allows us to check for rules in an arbitrary
      order without worrying as much about side-effects from previous rules.  It
      also allows us to add new rules with confidence than we won't kill
      something far down the chain.
    * Transform objects are immutable.
    * If the Transform needs to behave differently, make different Transform
      objects by initializing the same Transform class with different values.
      A Transform's transform_column() method should never take anything other 
      than one DataColumn or LazyColumn.
    
    The __call__ method is a wrapper that does caching and allows multiple 
    columns to be passed in.  The subclassing transform that you write needs to 
    implement the following:
    
    __init__ - Any number of params that you need to initialze the behavior of 
               an instance of a Transform.
    param_hash() - Set this to return a platform/run independent hash (like a
                   sha1) of the parameters you were initialized with.  Helpers
                   are in the cache module.
    transform_column - Method that takes exactly one Column and returns one 
                       Column (not LazyColumn, no extra arguments, and no other
                       params -- set those in the __init__).
    
    The actual use of a Transform might look something like:

    t_map_school_names = MappingTransform({"Nanakuli" : "Nanakuli Elementary"})
    m_school_name = t_map_school_names(c.school_name) 
    """ 
    def __call__(self, cg_or_col, stage=None):
        # We can add caching here later if we wish, and return DataColumns if we find them
        if isinstance(cg_or_col, ColumnGroup):
            return ColumnGroup([ (col_name, LazyColumn(col, self, stage=stage))
                                 for col_name, col in cg_or_col ] )
        elif isinstance(cg_or_col, Column):
            return LazyColumn(cg_or_col, self, stage=stage)
        else:
            raise TypeError, "Transforms can only be called on Columns or ColumnGroups"
    
    def __eq__(self, other):
        return type(self) == type(other) and \
               self.param_hash() and \
               self.param_hash() == other.param_hash()

    def __hash__(self):
        return hash(self.hash)

    @property
    def hash(self):
        return hashhelper.data_hash( hashhelper.source_hash(self.__class__),
                                     self.param_hash() )
    def param_hash(self):
        raise NotImplementedError("%s must implement param_hash()" % 
                                  self.__class__.__name__)
        
    def transform_column(self, col):
        raise NotImplementedError("Transforms must implement transform_column()" %
                                   self.__class__.__name__)

######################## PRIMITIVE BUILT-IN TRANSFORMS ########################

class MappingTransform(Transform):
    """A MappingTransform is used to create new columns based on old ones, with
    simple value substitutions where appropriate.  calculate_hash defaults to 
    True, but can be switched off when this transform is being used as part of
    another transform (since it's only used as an intermediate step there)."""
    
    def __init__(self, value_mapping):
        self._value_mapping = value_mapping
    
    def __repr__(self):
        return "MappingTransform(%s)" % repr(self._value_mapping)

    def param_hash(self):
        return hashhelper.data_hash(self._value_mapping)

    def transform_column(self, col):
        """Return a DataColumn (not LazyColumn) that is computed by performing all 
        the substitutions in our value_mapping (which we were passed in our
        initializer).
        
        @param A single column to be transformed
        """
        if not self._value_mapping:
            return col # No value mappings to translate, so just return the col

        # Only make a new Column if our mapping keys actually exist in the col
        mapping_keys = frozenset(self._value_mapping)
        if (mapping_keys & col.unique_values):
            return DataColumn([self._value_mapping.get(row_val, row_val) 
                               for row_val in col])
        else:
            return col


class MethodTransform(Transform):
    """Takes a method in the constructor and and transforms a column by applying
    that method to it.  The important thing here is that it's only looking at
    values themselves - the position of the values shouldn't matter."""
    
    def __init__(self, method):
        self._method = method
        self._param_hash = hashhelper.source_hash(method)

    def __repr__(self):
        return "MethodTransform(%s)" % repr(self._method)

    def param_hash(self):
        return self._param_hash

    def transform_column(self, col):
        # No need to go over every row, just the unique values...
        value_mapping = dict( ((val, self._method(val)) 
                               for val in col.unique_values) )
        return DataColumn( (value_mapping[val] for val in col) )


# Maybe we should just use a MethodTransform to do the normalizations.
#
#class NormalizeTransform(Transform):
#    
#    def __init__(self, std_to_aliases, calculate_hash=True):
#        """
#        @param std_to_aliases is a dict where the keys are standard values, and
#                              the values are lists of aliases.  For example:
#                              { True : [True, 'Y', 'YES'] }
#        """
#        args = {'std_to_aliases' : std_to_aliases, 'calculate_hash' : calculate_hash}
#        
#        self._std_to_aliases = std_to_aliases
#        value_mapping = {}
#        for std_val, alias_list in std_to_aliases.items():
#            for alias in alias_list:
#                if alias in value_mapping:
#                    message = "%s is an alias for both %s and %s" % \
#                              (alias, value_mapping[alias], std_val)
#                    raise TransformInitError(self, args, message)
#                else:
#                    value_mapping[alias] = std_val
#
#        self._mapping_transform = MappingTransform(value_mapping)
#        
#        if calculate_hash:
#            self.param_hash = cache.dict_hash(std_to_aliases)
#            
#    def transform_column(self, col):
#        return self._mapping_transform(col)

# normalize_booleans = MappingTransform({'y' : True, 'Y' : True})


####################### Not really working Transforms #######################

class Passing(Transform):
    """You would init this class with a Violations list.  After that, any time 
    it's invoked on a Column, it'll pass back a new Column that has the 
    row_value for every index that's not in the Violations list, and a None for
    all rows that are in the Violations list.  That way, after running a Rule,
    you can quickly get back all the rows of any Column that passed."""
    pass

class Failing(Transform):
    """See comment for Passing Transform"""
    def __init__(self, violations):
        """docstring for __init__"""
        pass

