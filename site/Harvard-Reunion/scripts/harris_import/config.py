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
    '2007' : {
        # Full Package
        '421914' : ['HR07-2', '421915', '421916', '421917', '421918'],
    },
    
    '2002' : {
        # Full Package
        '421957' : ['HR02-2', '421958', '421959', '421960', '421993', '421979'],
    },
    
    '1997' : {
        # Full Package
        '422013' : ['HR97-2', '422014', '422015', '422016', '422017', '422035', '422039'],
        # Tours of Harvard led by Crimson Key
        '422050' : ['HR97-6', 'HR97-7', 'HR97-8', 'HR97-9', 'HR97-10'],
    },
    
    '1992' : {
        # Full Package
        '422063' : ['HR92-2', '422069', '422064', '422065', '422066', '42206', '422086', '422056'],
    },
    
    '1987' : {
        # Full Package
        '420938' : ['HR87-2', 'HR87-3', 'HR87-5', 'HR87-6', 'HR87-7', 'HR87-8', 'HR87-9', 'HR87-10', 'HR87-11', 'HR87-12', 'HR87-13', 'HR87-14', 'HR87-15', 'HR87-16', 'HR87-17', 'HR87-18', 'HR87-19', 'HR87-20', 'HR87-21', 'HR87-22', 'HR87-23', 'HR87-24', 'HR87-26', 'HR87-28', 'HR87-29', 'HR87-30', 'HR87-31', 'HR87-32', 'HR87-33', 'HR87-34', 'HR87-35', 'HR87-36', 'HR87-37', 'HR87-38', 'HR87-39', '420952', '421052'],
        # Weekend Package
        '420952' : ['HR87-26', 'HR87-28', 'HR87-29', 'HR87-30', 'HR87-31', 'HR87-32', 'HR87-33', 'HR87-34', 'HR87-35', 'HR87-36', 'HR87-37', 'HR87-38', 'HR87-39', '421052'],
        # Thursday Day Program
        '420941' : ['HR87-9', 'HR87-10', 'HR87-11', 'HR87-12', 'HR87-13', 'HR87-14'],
        # Thursday Evening Program
        '420942' : ['HR87-15', 'HR87-16', 'HR87-17'],
        # Friday Day Program
        '420930' : [' HR87-18', 'HR87-19', 'HR87-20', 'HR87-21', 'HR87-22', 'HR87-23', 'HR87-24'],
        # Friday Evening Program
        '420926' : ['HR87-26', '421052'],
        # Saturday Day Program
        '420932' : ['HR87-28', 'HR87-30', 'HR87-31', 'HR87-32', 'HR87-33'],
        # Saturday Evening Program
        '420975' : ['HR87-35', 'HR87-36', 'HR87-37'],
        # Saturday Talent Show and "Club '87" Dance Party
        '420929' : ['HR87-36', 'HR87-37'],
        # Sunday Day Program
        '420974' : ['HR87-38', 'HR87-39'],
    },
    
    '1977' : {
        # Full Package
        '420824' : ['HR77-2', 'HR77-3', '420856', 'HR77-4', 'HR77-5', '420828', 'HR77-6', '420865', 'HR77-7', '420866', 'HR77-8', '420867', 'HR77-9', 'HR77-10', '420868', 'HR77-11', 'HR77-12', 'HR77-13', 'HR77-14', '420849', 'HR77-15', '420870', 'HR77-16', 'HR77-17', '420816', '420848'],
        # Thursday All Day
        '420827' : ['420828', 'HR77-6', '420865', 'HR77-7', '420866', 'HR77-8'],
        # Friday All Day
        '420817' : ['420867', 'HR77-9', 'HR77-10', '420868', 'HR77-11', 'HR77-12', 'HR77-13', 'HR77-14'],
        # Friday Class of '77 Dinner and Performance Event
        '420813' : ['HR77-12', 'HR77-13'],
        # Saturday All Day
        '420818' : ['420849', 'HR77-15', '420870', 'HR77-16', 'HR77-17', '420816'],
    },
    
    '1962' : {
        # Full Package
        '418183' : ['HR62-3', 'HR62-4', 'HR62-5', 'HR62-7', 'HR62-10', 'HR62-13', 'HR62-15', 'HR62-17', 'HR62-20', 'HR62-21', 'HR62-24', 'HR62-25', 'HR62-27', 'HR62-30', 'HR62-31', 'HR62-47'],
        # Monday
        '418201' : ['HR62-3', 'HR62-4', 'HR62-5'],
        # Tuesday
        '418189' : ['HR62-7', 'HR62-10', 'HR62-13', 'HR62-15'],
        # Tuesday Daytime and Dinner
        '418187' : ['HR62-7', 'HR62-10', 'HR62-13'],
        # Tuesday Daytime and Wednesday Daytime
        '420202' : ['HR62-7', 'HR62-10', 'HR62-13', 'HR62-17', 'HR62-20', 'HR62-21'],
        # All Tuesday and Wednesday Daytime
        '418186' : ['HR62-7', 'HR62-10', 'HR62-13', 'HR62-15', 'HR62-17', 'HR62-20', 'HR62-21'],
        # Wednesday
        '418202' : ['HR62-17', 'HR62-20', 'HR62-21', 'HR62-24', 'HR62-47', 'HR62-25'],
        # Wednesday Daytime Only
        '418203' : ['HR62-17', 'HR62-20', 'HR62-21'],
        # Wednesday Dinner Only
        '418204' : ['HR62-24', 'HR62-47', 'HR62-25'],
        # Thursday
        '418205' : ['HR62-27', 'HR62-30', 'HR62-31'],
    },
    
    'H1957' : {
        # Full Package
        '422179' : ['HR57-2', 'HR57-8', 'HR57-14', 'HR57-23', 'HR72-27', 'HR57-33'],
        # Monday
        '422182' : ['HR57-2', 'HR57-3'],
        # Tuesday
        '422183' : ['HR57-5', 'HR57-6', 'HR57-7', 'HR57-8', 'HR57-10', 'HR57-11', 'HR57-13', 'HR57-14', 'HR57-15'],
        # Wednesday
        '422181' : ['HR57-20', 'HR57-21', 'HR57-22', 'HR57-23', 'HR57-27', 'HR57-28,'],
    },
    
    'R1957' : {
        # Full Package
        '422844' : ['422848', '422846', '422856', '422857', 'RR57-11'],
        # Gardner Museum Event
        '422847' : ['RR57-11', 'RR57-12'],
    },
    
    'H1952' : {
        # Full Package
        '419681' : ['419682', 'HR52-5', 'HR52-6', 'HR52-39', 'HR52-10', 'HR52-14', 'HR52-15', 'HR52-16', 'HR52-40', '419677', '419676', 'HR52-24', 'HR52-25', 'HR52-30', 'HR52-31', 'HR52-33'],
        # Tuesday. Includes symposia, memorial service, lunch, and clambake on campus
        '419683' : ['HR52-5', 'HR52-39', 'HR52-6', 'HR52-10'],
        # Wednesday. Includes symposia, lunch, cocktail reception, dinner and cabaret entertainment at the Fairmont Copley Hotel
        '419680' : ['HR52-14', 'HR52-15', 'HR52-16', 'HR52-40', '419677', '419676', 'HR52-24', 'HR52-25'],
    },
    
    'R1952' : {
        # Full Package
        '422861' : ['422863', '422862', '422876', '422860', 'RR52-10', 'RR52-11', 'RR52-14', 'RR52-15'],
    },
    
    'H1947' : {
        # Full Package
        '422681' : ['422667', 'HR47-4', 'HR47-7', 'HR47-13', 'HR47-14', 'HR47-17', 'HR47-18', 'HR47-20', 'HR47-27', 'HR47-28', 'HR47-32', 'HR47-33', 'HR47-37'],
        # Tuesday symposia, memorial service, lunch at the Harvard Faculty Club
        '422678' : ['HR47-4', 'HR47-5', 'HR47-7'],
        # Tuesday night dinner and Boston Pops concert at Symphony Hall
        '422686' : ['HR47-13', 'HR47-14'],
        # Wednesday symposia and lunch
        '422684' : ['HR47-17', 'HR47-18', 'HR47-20'],
        # Wednesday night cocktail reception and dinner at the Harvard Faculty Club and the Harvard Band and Glee Club concert
        '422683' : ['HR47-27', 'HR47-28'],
    },
    
    'R1947' : {
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
