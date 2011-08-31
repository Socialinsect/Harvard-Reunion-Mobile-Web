import sys
from collections import namedtuple
from itertools import islice, product

from csvcolumns.column import DataColumn
from csvcolumns.transform import MethodTransform

User = namedtuple('User', 'first last email')

FIRST_NAMES = ["Sonya", "Brian", "Jim", "Jimmy", "James", "David", "Dave",
               "Alexis", "Yuki", "Eric", "Ilona", "Andy", "Andrew", "Pat",
               "Patricia", "Gopal", "Charles", "Charlie", "Hoon", "Pete",
               "Peter", "Piotr", "Muhammad", "Josh", "Joshua", "Bob", "Robert",
               "Katherine", "Kathy", "Cathy", "Catherine"]

LAST_NAMES = ["Huang", "Patt", "Tepfenhart", "Akins", "Ellwood", "Swamy",
              "Chisholm", "Moreland", "Amjad", "Nagatoshi", "Yu", "Kim", "Lee",
              "Mallon", "Ormsbee", "Smith", "Jones", "Bailey", "Adams", 
              "Leverett", "Quincy", "Winthrop", "Kirkland", "Dunster", "Mather",
              "Cabot", "North", "Currier", "Pforzheimer", "Lowell"]

TEST_USERS = {
    '1981' : [ User("Alex", "Smith", "modo.asmith@gmail.com") ],
    # This guy should never be called, but he's here for completeness...
    '1982' : [ User("Chris", "Doe", "modo.cdoe@gmail.com"),
               User("Dana", "Park", "modo.dpark@gmail.com"),
               User("Dan", "Smith", "modo.dsmith@gmail.com"),
               User("Pat", "Leary", "modo.pleary@gmail.com") ],
    '1971' : [ User("John", "Smith", "modo.jpark@gmail.com"),
               User("Jason", "Park", "modo.jsmith@gmail.com"),
               User("Jane", "Doe", "modo.jdoe@gmail.com"),
               User("Janice", "Fisher", "modo.jfisher@gmail.com") ],
    '1966' : [ User("Cynthia", "Fisher", "modo.cfisher@gmail.com"),
               User("Darin", "Fisher", "modo.dfisher@gmail.com"),
               User("Mary", "Park", "modo.mpark@gmail.com"),
               User("Shanon", "Doe", "modo.sdoe@gmail.com") ],
    #'2006' : [ User("Alex", "Smith", "modo.asmith@gmail.com") ],
    #'2001' : [ User("John", "Smith", "modo.jpark@gmail.com"),
    #           User("Jason", "Park", "modo.jsmith@gmail.com"),
    #           User("Jane", "Doe", "modo.jdoe@gmail.com"),
    #           User("Janice", "Fisher", "modo.jfisher@gmail.com") ],
    #'1996' : [ User("Cynthia", "Fisher", "modo.cfisher@gmail.com") ],
    #'1991' : [ User("Joe", "Leary", "modo.jleary@gmail.com") ],
    #'1986' : [ User("Darin", "Fisher", "modo.dfisher@gmail.com") ],
    #'1976' : [ User("Mary", "Park", "modo.mpark@gmail.com") ],
    #'1961' : [ User("Shanon", "Doe", "modo.sdoe@gmail.com") ],
    #'1956' : [ User("Dana", "Park", "modo.dpark@gmail.com") ],
    #'1951' : [ User("Dan", "Smith", "modo.dsmith@gmail.com") ],
    #'1946' : [ User("Pat", "Leary", "modo.pleary@gmail.com") ],
}

def generate_users():
    for i, name in enumerate(sorted(product(FIRST_NAMES, LAST_NAMES))):
        order_id = "testID%s" % i
        first, last = name
        email = "%s.%s@modolabs.com" % (first, last)
        yield User(first, last, email)

def anonymize_users(user_cols, year):
    num_users = user_cols.num_rows
    test_users = list(TEST_USERS[year])
    random_users = list(islice(generate_users(), num_users - len(test_users)))
    all_anon_users = random_users + test_users
    
    first_names, last_names, emails = zip(*all_anon_users)

    return user_cols.replace(email=DataColumn(emails),
                             first_name=DataColumn(first_names),
                             last_name=DataColumn(last_names))
    
def anonymize_events(event_cols):
    """A lot of time information is placed into the event columns -- stuff like
    where you'd like to be, what your kids' names are, who you'd like to room 
    with over the weekend, etc. So we're going to go through and simply convert
    all of these potential values to 1's, to indicate that the user has selected
    it. This might cause problems for things like 'Where are you eating?', but
    should work for most cases."""
    normalize_to_ones = MethodTransform(lambda x: "1" if x else "")
    return normalize_to_ones(event_cols)
