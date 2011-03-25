"""
Some events actually map to many other events -- package deals that are 
purchased as a single line item in Harris and therefore come across as a single
'event'.

Note that all EventIDs and Classes are strings, because we can have things like
1961R (Radcliffe) or events that are actually not really existant in Harris, but
tracked anyway (like hr50-01).
"""
# Right now these are just the ones for class of 2001
NON_HARRIS_EVENTS = ['h01-1', 'h01-2', 'h01-3']

CLASSES_TO_MAPPINGS = {
    '2001' : {
        # Full Package
        '391673' : ['391679', '391674', '391681', '391675', '391709', '391676', '391677',
                    # I'm making up this part:
                    'h01-1']
    }
}
