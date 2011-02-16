#################
Device Detection
#################

One of the strong features of the Kurogo framework is the ability to detect various devices and 
format content based on that device's capabilities. To support the classification of devices, the 
framework uses a Device Detection Server that contains a database of devices and outputs a normalized
set of properties.

=================================
Types of Device Detection Servers
=================================

Kurogo includes an internal device detection server that parses the user agent of the user's device
and returns an appropriate series of values. It contains a SQLite database that contains a series
of patterns and will return the values that match that pattern. This allows you to control the entire
process of detecting devices. 

There is also an external device detection service available. The advantage of this service is that it
will contain a more up to date database of new devices. There are 2 urls available. One is suitable for
development and one for production. 

See :ref:`Device Detection Configuration <devicedetection_config>`  for specific configuration values.

===========
Data Format
===========

The Kruogo Framework queries the device detection service using the *user agent* of the user's browser.
The service will then return a series of properties based on the device:

* *pagetype* - String. One of the device *buckets* that determines which major source of HTML the device
  will received. Values include *BASIC*, *TOUCH* and *WEBKIT* (aka *COMPLIANT*)
* *platform* - The specific type of device. Values include *ANDROID*, *BBPLUS*, *BLACKBERRY*, *COMPUTER*, 
  *FEATUREPHONE*, *IPHONE*, *PALMOS*, *SPIDER*, *SYMBIAN*, *WEBOS*, *WINMO*, *WINPHONE7*
* *supports_certificates* - Boolean. Whether this devices supports certificate based authentication
* *description* - a textual description of the device
