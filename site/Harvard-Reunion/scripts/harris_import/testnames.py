import sys
from itertools import product

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


def main(class_year):
    for i, name in enumerate(sorted(product(FIRST_NAMES, LAST_NAMES))):
        order_id = "testID%s" % i
        first, last = name
        email = "%s.%s@modolabs.com" % (first, last)
        # order_id, order_date, store, email, class_year, display, status,
        # credit_request, bill_prefix, bill_first_name, bill_last_name
        print "\t".join((order_id, "2011-04-05", "0", email, class_year, "1", "Pending",
                         "0", "", first, last))


if __name__ == '__main__':
    main(sys.argv[1])