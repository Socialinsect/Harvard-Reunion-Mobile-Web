"""
Some events actually map to many other events -- package deals that are 
purchased as a single line item in Harris and therefore come across as a single
'event'.

Note that all EventIDs and Classes are strings, because we can have things like
1961R (Radcliffe) or events that are actually not really existant in Harris, but
tracked anyway (like hr50-01).

    'H1956' : {
        # Full Reunion Package
        '393929' : ['393934', 'h56-2', 'h56-3', 'h56-4', 'h56-5', 'h56-6', 
                    'h56-7', 'h56-8', 'h56-9', 'h56-10', 'h56-11', 'h56-12', 
                    'h56-13', 'h56-14', 'h56-15', 'h56-16', 'h56-17', 'h56-18',
                    'h56-20', 'h56-21', 'h56-22', 'h56-23', 'h56-24', 'h56-25',
                    'h56-26', 'h56-27', 'h56-28', 'h56-29', 'h56-30', 'h56-31',
                    'h56-32', 'h56-34', 'h56-35', 'h56-36', 'h56-37'],
        # Tuesday MFA
        '393935' : ['h56-2', 'h56-3', 'h56-4', 'h56-5', 'h56-6', 'h56-7'],
        # Tuesday dinner and Pops concert
        '393933' : ['h56-8', 'h56-9', 'h56-10', 'h56-11', 'h56-12'],
        # Wednesday daytime events
        '393949' : ['h56-13', 'h56-14', 'h56-15', 'h56-16', 'h56-17', 'h56-18', 
                    'h56-20', 'h56-21'],
        # Wednesday Dinner with Dancing
        '393950' : ['h56-22', 'h56-23', 'h56-24', 'h56-25', 'h56-26', 'h56-27'],
    },
    
    'R1956' : {
        # Full Reunion Package
        '394804' : ['r56-1', 'r56-2', 'r56-3', 'r56-4', 'r56-5', 'r56-6', 
                    'r56-7', 'r56-8', 'r56-9', 'r56-10', 'r56-11', 'r56-12', 
                    'r56-13', 'r56-14', 'r56-15', 'r56-16', 'r56-18', 'r56-19',
                    'r56-20', 'r56-21', 'r56-22', '394817', 'r56-23', 'r56-24',
                    'r56-25', 'r56-26', 'r56-27', 'r56-28', 'r56-29', 'r56-31',
                    'r56-32', 'r56-33', 'r56-34', '394807'],
        # Tuesday MFA
        '394808' : ['r56-1', 'r56-2', 'r56-3', 'r56-4', 'r56-5', 'r56-6'],
        # Tuesday dinner and Pops concert
        '394806' : ['r56-7', 'r56-8', 'r56-9', 'r56-10', 'r56-11'],
        # Wednesday daytime events
        '394816' : ['r56-12', 'r56-13', 'r56-14', 'r56-15', 'r56-16', 'r56-18', 
                    'r56-19']
    },

    '1961' : {
        # Full Reunion Package
        '385620' : ['hr61-3', 'hr61-4', 'hr61-5', 'hr61-6', 'hr61-7', 'hr61-8', 
                    'hr61-9', 'hr61-10', 'hr61-11', 'hr61-12', 'hr61-13', 'hr61-14',
                    'hr61-15', 'hr61-16', 'hr61-17', 'hr61-18', 'hr61-19', 'hr61-20',
                    'hr61-21', 'hr61-22', 'hr61-23', 'hr61-24', 'hr61-25', 'hr61-26',
                    'hr61-27', 'hr61-28', 'hr61-29', 'hr61-30', 'hr61-31', 'hr61-32',
                    'hr61-33', 'hr61-34', 'hr61-36'],
        # Tuesday and Wednesday Daytime
        '385622' : ['hr61-7', 'hr61-8', 'hr61-9', 'hr61-10', 'hr61-11', 'hr61-12',
                    'hr61-13', 'hr61-18', 'hr61-19', 'hr61-20', 'hr61-21', 'hr61-22', 
                    'hr61-23', 'hr61-24'],
        # Tuesday all Events and Meals and Wednesday Daytime
        '385623' : ['hr61-7', 'hr61-8', 'hr61-9', 'hr61-10', 'hr61-11', 'hr61-12',
                    'hr61-13', 'hr61-14', 'hr61-15', 'hr61-16', 'hr61-17', 'hr61-18',
                    'hr61-19', 'hr61-20', 'hr61-21', 'hr61-22', 'hr61-23', 'hr61-24'],
        # Monday
        '385625' : ['hr61-3', 'hr61-4', 'hr61-5', 'hr61-6'],
        # Tuesday
        '385626' : ['hr61-7', 'hr61-8', 'hr61-9', 'hr61-10', 'hr61-11', 'hr61-12',
                    'hr61-13', 'hr61-14', 'hr61-15', 'hr61-16', 'hr61-17'],
        # Tuesday Daytime Only
        '385624' : ['hr61-7', 'hr61-8', 'hr61-9', 'hr61-10', 'hr61-11', 'hr61-12',
                    'hr61-13'],
        # Wednesday
        '387627' : ['hr61-18', 'hr61-19', 'hr61-20', 'hr61-21', 'hr61-22', 'hr61-23', 
                    'hr61-24', 'hr61-25', 'hr61-26', 'hr61-27'],
        # Wednesday Daytime Only
        '387628' : ['hr61-18', 'hr61-19', 'hr61-20', 'hr61-21', 'hr61-22', 'hr61-23',
                    'hr61-24'],
        # Wednesday Dinner Only
        '387629' : ['hr61-25', 'hr61-26', 'hr61-27'],
        # Thursday
        '387630' : ['hr61-28', 'hr61-29', 'hr61-30', 'hr61-31', 'hr61-32',
                    'hr61-33', 'hr61-34'],
    },
    
    '1976' : {
        # Full Package
        '387939' : ['hr76-2', 'hr76-3', '387986', 'hr76-4', 'hr76-5', '387943', 'hr76-6', 
                    '388004', 'hr76-7', 'hr76-8', 'hr76-9', 'hr76-10', '388011', 'hr76-11',
                    'hr76-12', '388012', 'hr76-13', 'hr76-14', 'hr76-15', 'hr76-16', 
                    '388013', 'hr76-17', '387979', 'hr76-18', '387976', 'hr76-19', 
                    'hr76-20', 'hr76-21', '388017', 'hr76-22', 'hr76-23', 'hr76-24', 
                    'hr76-26', 'hr76-27', 'hr76-28', '387975'],
        # Thursday All Day
        '387942' : ['387943', 'hr76-6', '388004', 'hr76-7', 'hr76-8', 'hr76-9'],
        # Thursday Dinner and Movie
        '388010' : ['hr76-8', 'hr76-9'],
        # Friday All Day
        '387931' : ['388011', 'hr76-11', 'hr76-12', '388012', 'hr76-13', 'hr76-14',
                    'hr76-15', 'hr76-16', '388013', 'hr76-17', '387979', 'hr76-18'],
        # Friday Pre-Pops Dinner, Boston Pops, and Post-Pops Reception
        '387927' : ['388013', 'hr76-17', '387979', 'hr76-18'],
        # Saturday All Day
        '387933' : ['387976', 'hr76-19', 'hr76-20', 'hr76-21', '388017', 'hr76-22', 
                    'hr76-23', 'hr76-24', 'hr76-26', 'hr76-27', 'hr76-28'],
        # Saturday Talent Show and Gala Dinner Dance
        '387930' : ['hr76-27', 'hr76-28']
    },
    
    '1986' : {
        # Full Package
        '387655' : ['387759',  'hr86-5',  'hr86-7',  'hr86-8',  'hr86-9',  'hr86-10', 
                    'hr86-11', 'hr86-13', 'hr86-14', 'hr86-15', 'hr86-17', 'hr86-18',
                    'hr86-19', 'hr86-20', 'hr86-21', 'hr86-22', 'hr86-23', '387712', 
                    'hr86-24', 'hr86-25', 'hr86-26', 'hr86-28', 'hr86-30', 'hr86-31',
                    'hr86-33', 'hr86-34', 'hr86-35', 'hr86-36', 'hr86-37', 'hr86-38', 
                    'hr86-39'],
        # Weekend Package
        '387685' : ['hr86-22', 'hr86-23', '387712',  'hr86-24', 'hr86-25', 'hr86-26',
                    'hr86-28', 'hr86-30', 'hr86-31', 'hr86-33', 'hr86-34', 'hr86-35',
                    'hr86-36', 'hr86-37', 'hr86-38', 'hr86-39'],
        # Thursday Day Program
        '387659' : ['hr86-5', 'hr86-7', 'hr86-8', 'hr86-9', 'hr86-10'],
        # Thursday Evening Program
        '387660' : ['hr86-11', 'hr86-13', 'hr86-14'],
        # Friday Day Program
        '387664' : ['hr86-15', 'hr86-17', 'hr86-18', 'hr86-19', 'hr86-20', 'hr86-21'],
        # Friday Evening Program
        '387665' : ['hr86-22', 'hr86-23', '387712', 'hr86-24', 'hr86-25'],
        # Saturday Day Program
        '387670' : ['hr86-26', 'hr86-28', 'hr86-30', 'hr86-31'],
        # Saturday Evening Program
        '387709' : ['hr86-33', 'hr86-34', 'hr86-35'],
        # Saturday Talent Show and "Club '86" Dance Party, Only
        '387671' : ['hr86-34', 'hr86-35'],
        # Sunday Day Program
        '387736' : ['hr86-36', 'hr86-37', 'hr86-38', 'hr86-39'],
    },
    
    '1991' : {
        # Full Package
        '392034' : ['392040', '392156', '392035', '392036', '392037', '392153', '392038'],
    },
    
    '1996' : {
        # Full Package
        '391858' : ['391859', '391860', '391883', '391861', '391921', '391862'],
    },
    
    '2001' : {
        # Full Package
        '391673' : ['391679', '391674', '391681', '391675', '391676', '391677', '391709'],
    },
    
    '2006' : {
        # Full Package
        '391136' : ['391142', '391137', '391138', '391139', '391144', '391140'],
    },
"""

from itertools import chain

CLASSES_TO_PACKAGE_EVENT_MAPPINGS = {
    '1981' : {
        # Full Reunion Package
        '403093' : ['hr81-3', 'hr81-5', 'hr81-6', 'hr81-7', 'hr81-8', 'hr81-9', 
                    'hr81-10', 'hr81-11', 'hr81-13', 'hr81-14', 'hr81-17', 
                    'hr81-18', '403096', 'hr81-19', 'hr81-20', '403098'],
        # Friday Football Game and Dinner
        '403095' : ['hr81-9', 'hr81-10', 'hr81-11', 'hr81-13', 'hr81-14'],
    },
    
    '1971' : {
        # Full Reunion Package
        '403446' : ['403447', 'hr71-2', 'hr71-3', 'hr71-4', 'hr71-5', 'hr71-6', 
                    '403437', 'hr71-7', 'hr71-8', 'hr71-9', '403434', 'hr71-11', 
                    'hr71-12', '403440', 'hr71-13', 'hr71-14', 'hr71-15', 
                    'hr71-16', 'hr71-17', '403433'],
        # Saturday Dinner Dance at Northwest Labs, 52 Oxford St.
        '403445' : ['hr71-16', 'hr71-17'],
    },
    
    '1966' : {
        # Full Reunion Package
        '404711' : ['404717', 'hr66-0', 'hr66-1', 'hr66-2', 'hr66-3', 'hr66-4', 
                    'hr66-5', 'hr66-6', 'hr66-7', 'hr66-8', 'hr66-9', 'hr66-11', 
                    'hr66-12', 'hr66-13', 'hr66-14', 'hr66-15', 'hr66-16', 
                    'hr66-17'],
        # Thursday Reception
        '404716' : ['hr66-0', 'hr66-1'],
        #	Friday Tailgate Dinner
        '404715' : ['hr66-6', 'hr66-7', 'hr66-8', 'hr66-9'],
        # Saturday Lunch
        '404731' : ['hr66-13', 'hr66-14'],
        # Saturday Cocktails and Dinner
        '404732' : ['hr66-16', 'hr66-17'],
    },
}

def non_harris_events_for_year(year):
    if year not in CLASSES_TO_PACKAGE_EVENT_MAPPINGS:
        return []
        
    event_id_lists = CLASSES_TO_PACKAGE_EVENT_MAPPINGS[year].values()
    return frozenset(event_id for event_id in chain(*event_id_lists)
                     if not event_id.isdigit())
    
def packages_for_year(year):
    return CLASSES_TO_PACKAGE_EVENT_MAPPINGS.get(year, {});
