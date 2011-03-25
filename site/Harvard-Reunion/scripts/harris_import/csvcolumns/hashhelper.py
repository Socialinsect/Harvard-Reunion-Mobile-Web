import hashlib
import inspect
import json

def source_hash(x):
    """Return an interpreter independent hash string of the class (so the value
    should be the same no matter what machine it's run on.)  This doesn't work
    on things entered interactively, or built-in classes/functions.
    
    Lambdas aren't cached properly if they're used in multiple places because
    it takes the whole line that the lambda is invoked on, not just the lambda
    itself.
    """
    try:
        str_to_hash = inspect.getsource(x).strip()
    except IOError:
        # If we can't get the source (like a lambda) we give up machine-independence
        str_to_hash = repr(x)
    
    return str_hash(str_to_hash) # Stripping because of lambdas

def data_hash(*data):
    """Return a hash string when given a simple Python data structure (lists, tuples,
    dicts, strings)"""
    return str_hash(json.dumps(data))

def str_hash(s):
    return hashlib.sha1(s).hexdigest()
