"""
column.py

Columns and ColumnGroups ARE OUTWARDLY IMMUTABLE.  DON'T CHANGE THIS!  This 
decision was very intentional, and was done to allow for the caching of long
chains of transforms and juggling many versions of a column that might be
necessary to implement complicated business rules for transforms.  Side effects
are a nightmare in these situations.

* All columns represent raw data sequences.  They have no explicit names 
  because they may be used in many contexts.  They may hold any type of data
  (not just strings).
* DataColumns are the basic Column type, and need to be initialized with a 
  Python iterable of some sort.
* LazyColumns are place-holders for column transformations that may never 
  actually need to be computed.  They will execute their transform if asked
  for their contents.

Columns have two kinds of hashes:

Identity hash
    Uniquely identifies a Column. In a DataColumn, it's the same as the value
    hash. In a LazyColumn, it's the combination of the source column's identity
    hash and the hash of the transform.
Value hash
    SHA1 hash of the actual computed contents of the column. This is only loaded
    on demand for lazy columns.
               
Both kinds of hashes are platform independent. For LazyColumns, identity hashes
are easy/quick to calculate, but may give duplicates where the resulting
column values are the same as another transform acting on another column.
Identity hashes for LazyColumns are also not guaranteed to be consistent over 
time -- something called by a Transform might change its implementation, causing
the identity hash to no longer correlate to the same final value.

For these reasons, identity hashes should not be counted on for long term
stability. When serializing something, use the value hash (which is guaranteed
not to change). Identity hashes can still be used for short term caching though
(e.g. memcache with an identity hash to value hash mapping).

Other, memory-optimized Column types to consider making at some point:
* column that holds a reference to a backing DataColumn and a simple dict 
  mapping for simple substitutions.
* an enumeration column that represents a limited number of possible values
  as bit combos to save space (so 00 = "ACTIVE", 01 = "PENDING", 10 = "DELETED")
  -- good for when you have a small range of values randomly distributed (gender,
  status, etc.)
* for values that are grouped together, something that shows values and 
  boundaries.  (0, "Nanaikapono"), (2030, "Nanakuli") would mean that indexes
  0-2029 are "Nanaikapono", and everything from 2030 up are "Nanakuli".
* calculation driven columns that never store their calculated values, just
  the backing DataColumn and the transformation method -- if the transform is
  really cheap and memory important.  Need to profile to see if this is even
  worthwhile.
"""
import json
from cStringIO import StringIO
from itertools import count, izip

import hashhelper

MAX_REPR_LIST_SIZE = 5 # How many entries do we show in a __repr__ call?

class Column(object):

    def __eq__(self, other):
        """If the identity hashes or the value hashes match each other, it's
        the same.  But value hashes aren't always present, because they're
        expensive to compute."""
        if id(self) == id(other):
            return True # Our simplest case, no need to load hashes
        else:
            return (self.identity_hash is not None) and \
                   (self.identity_hash == other.identity_hash)
                   
    def to_json(self):
        if not hasattr(self, '_json_repr'):
            self._json_repr = json.dumps(self._row_values)
        return self._json_repr

    def __hash__(self):
        """Use our identity hash as the basis for Python's hash() builtin"""
        return hash(self.identity_hash) # Python 2.4 and below want a 32-bit value
    

class DataColumn(Column):
    """A DataColumn object represents an actual column of data which is has been
    fully calculated (no caching at this level).  In many ways, it's like a 
    thin wrapper over a list of strings.  Some key points:

    * DO NOT CHANGE THE CONTENTS/HASH OF A COLUMN AFTER IT HAS BEEN CREATED.
      A DataColumn is immutable (many later caching mechanisms depend on this).
    * You can iterate over a DataColumn's contents or grab a specific index, as
      you would with a list.  "col[i]", "for row_val in col:", etc.
    * You can get a set of all unique values in this DataColumn using 
      .unique_values -- this is often helpful for transforms or rule checking.
    * A DataColumn only knows about the data in its rows. It does not know its 
      name (the same column of data could have different names in different
      contexts). This is also makes caching a little more flexible, since the 
      fingerprints of two columns can be the same even if they're used for 
      different things.
    """
    
    def __init__(self, row_values, explicit_hash=None):
        """
        Create a DataColumn from some list/sequence of strings.  We can also
        calculate the hash of the values in the sequence.  We would do 
        this only when creating the original columns from the CSV source file.
        Transforms should always be returning the same Column they were given
        (in the case where there was nothing to change), or they should return 
        a LazyColumn, which has its own hashing mechanism based upon its source
        column and the transform it performed.
        
        @param row_values is a sequence of values, typically strings.
        @param explicit_hash is an SHA1 hash digest string, useful if we're 
                             loading this DataColumn from a cache and already
                             know the appropriate hash value.
        """
        self._row_values = tuple(row_values)
        self._unique_values = None # only computed when it's asked for

        # Set if it was passed in. If no explicit hash was passed in, it is
        # set to None, and we will compute the hash when it's requested.
        self._value_hash = explicit_hash

    @property
    def identity_hash(self):
        """Return a hash that uniquely identifies us.  For DataColumns, this is
        the same as the value_hash."""
        return self.value_hash
    @property
    def value_hash(self):
        """Return a SHA1 hash of the contents of this DataColumn."""
        if self._value_hash is None:
            self._value_hash = hashhelper.data_hash(self._row_values)
        return self._value_hash

    @property
    def original_col(self):
        return self
        
    @property
    def source_col(self):
        return self
    
    # Let's keep the JSON serialization/deserialization internal
    #@property
    #def row_values(self):
    #    """Return the tuple with all our row values in it.  You can use the 
    #    DataColumn object itself to do iteration or get things by index.  The
    #    only reason this method exists is to aid serialization methods that
    #    want the Python primitive types underneath."""
    #    return self._row_values

    @property
    def unique_values(self):
        """Return a frozenset of the unique values in this DataColumn."""
        if self._unique_values is None:
            self._unique_values = frozenset(self._row_values)
        return self._unique_values

    
    def __len__(self):
        return len(self._row_values)
        
    def __getitem__(self, index):
        return self._row_values[index]

    def __iter__(self):
        return iter(self._row_values)

    def iter_rows(self, count_from=1):
        """Acts like the enumerate() built-in, except it defaults the count
        start from 1 instead of 0 -- since that's what you'd typically want to
        display, and if you wanted to start from 0, you could just use
        enumerate().
        
        Yeah, Python 2.6 lets you pass a start value to enumerate() making
        this all unnecessary. :-P """
        return izip(count(count_from), self)

    def __repr__(self):
        return "<DataColumn, size: %s, unique: %s, hash: %s, sample: %s>" % \
               (len(self._row_values), len(self.unique_values), self._value_hash,
                self._row_values[0:MAX_REPR_LIST_SIZE])
    
    def __str__(self):
        # FIXME: This is kinda screwy for single element columns
        if len(self._row_values) == 1:
            return str(self._row_values[0])
        else:
            return '\n'.join(self._row_values)
        

class LazyLoadError(Exception):
    """A ColumnLoadError is raised if there's a problem while running the
    transformation to load the contents of a LazyColumn.""" 
    def __init__(self, transform, column, original_exception):
        self.transform = transform
        self.column = column
        self.original_exception = original_exception

    def __str__(self):
        return "Transform %s failed for column %s... Original error: %s" % \
               (repr(self.transform), repr(self.column), self.original_exception)


class LazyColumn(Column):
    """A LazyColumn is a wrapper around a Column that hopes to delay computation
    of a transform.  Running a Transform's __call__ method generates one of 
    these.  As far as the outside world is concerned, it can be treated like a 
    DataColumn.  It's just, you know, lazy about loading itself.
    
    You should never have to create one of these explicitly, as that wrapping
    is done by the Transform object.  When you write your own transforms, you
    should only worry about creating the computed DataColumn, and leave the
    wrapping to the Transform super-class.
    
    Since rule Violations are cached based on these hashes, we could potentially
    fetch the rule results for a series of transforms without ever having to 
    actually do them ourselves.  This is especially true since validation often
    involves making small changes to the data file and resubmitting.  This is 
    why we're computing all these SHA1 hashes, even though at the moment we're 
    not doing external caching of LazyColumns.
    
    
    """

    def __init__(self, source_col, transform, stage=None):
        """
        @param source_col is the original Column or LazyColumn that this object
                          represents a transformed version of.
        @param transform should be a Transform object.
        @param stage is a string identifier for what this particular stage
                     of transformation represents.  This is so we can later
                     go through a series of transforms on a Column and
                     search for key transformation points.  Stage names should
                     be unique, so you cannot set a stage name that is the same
                     as any LazyColumn that exists in our list of ancestors.
        """
        self._source_col = source_col
        self._transform = transform
        self._original_col = source_col.original_col
        
        # DataColumns don't have stage names, so search all the way up until 
        # the end
        for lazy_col in self.transformed_ancestors: 
            if (stage is not None) and (stage == lazy_col.stage):
                raise ValueError("Cannot create LazyColumn with stage '%s' " \
                                 "because this identifier is used in ancestor"\
                                 " column %s " % (stage, lazy_col))
        self._stage = stage

        # FIXME: The hashing mechanism for transforms needs work...
        self._identity_hash = hashhelper.data_hash( source_col.identity_hash,
                                                    hash(transform) )
        
        # _transformed_col says: Don't look at me from outside!  I don't have a
        # hash, and throwing me around outside of this warm, cozy LazyColumn 
        # could have bad and not immediately apparent effects on performance.
        self._transformed_col = None        

    # TODO: Fix this somewhat evil column_lookup caching
    def _load(self, column_lookup={}):
        """Calculate and load the actual data for this column, by invoking its
        Transform.  This is delayed until we actually need to read a transformed
        value."""
        if self._transformed_col is None:
            # FIXME: This cache lookup code doesn't do anything at the moment.
            if self.identity_hash in column_lookup:
                self._transformed_col = column_lookup[self.identity_hash]
            else:
                self._transformed_col = self._transform.transform_column(self.source_col)
#                try:
#                    self._transformed_col = self._transform.transform_column(self.source_col)
#                except Exception, ex:
#                    raise LazyLoadError(self._transform, self._source_col, ex)

    @property
    def identity_hash(self):
        """Return a hash that uniquely identifies us.  For LazyColumns, this is
        a combination of our source column's hash, and our transform's hash."""
        return self._identity_hash
        
    @property
    def value_hash(self):
        self._load()
        return self._transformed_col.value_hash

    @property
    def original_col(self):
        return self._original_col
        
    ############### Public methods specific to LazyColumns ##############
    @property
    def source_col(self):
        return self._source_col
    @property
    def transform(self):
        return self._transform
    
    @property
    def stage(self):
        return self._stage
        
    @property
    def ancestors(self):
        """Return a list of all ancestor columns of this one."""
        ancestor_list = []
        col = self
        while col.source_col != col: # Until we hit the DataColumn at the top
            ancestor_list.append(col.source_col)
            col = col.source_col
        return ancestor_list
    
    @property
    def transformed_ancestors(self):
        """Return a list of ancestors that have been transformed -- in other
        words, the ancestors minus the root original DataColumn that they were
        derived from."""
        return self.ancestors[:-1]
    
    def history(self, stage):
        """We're searching for a transformation stage by stage name. We have
        to include ourselves in the search, but we should not include the
        original source DataColumn (because that has no stage attribute -- the
        same DataColumn can be used as a jumping off point for many things and
        represent different columns in the source data, just with identical
        values.)"""
        for col in [self] + self.transformed_ancestors:
            if (stage is not None) and (col.stage == stage):
                return col
        raise KeyError("Stage %s not found in ancestor history for %s" % 
                       (stage, self))
    
    def __repr__(self):
        return "<LazyColumn %s, stage: %s, loaded?: %s, transform: %s, src col: %s>" % \
               (str(self.identity_hash), 
                self.stage,
                self._transformed_col and "yes" or "no",
                repr(self.transform), repr(self.source_col))
               
    def __str__(self):
        self._load()
        return str(self._transformed_col)
    
    def __iter__(self):
        self._load()
        return iter(self._transformed_col)
    
    def __getitem__(self, i):
        self._load()
        return self._transformed_col[i]
        
    def __len__(self):
        return len(self._source_col)
        
    # The following are methods that we just proxy out to our underlying 
    # transformed column.
    #
    # The problem is that for many attributes, it's not actually calling
    # those attributes, but searching the object hierarchy for class __dict__
    # to check if those values are in there.  So basically, we need a shorthand
    # way of specifying the methods we're going to forward, and have them
    # dynamically generated at runtime.  Something like this in the class 
    # definition:
    #
    # class LazyColumn(Column):
    #     proxy( self._obj, _load
    #            ['__getitem__', '__iter__', 'iter_rows', 'unique_values'])
    #
    #
    def __getattr__(self, name):
        if hasattr(DataColumn, name):
            self._load()
            setattr(self, name, getattr(self._transformed_col, name))
            return getattr(self._transformed_col, name)
        else:
            err_msg = "attribute %s not found in LazyColumn or DataColumn" % name
            raise AttributeError(err_msg)
                  