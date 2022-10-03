# listeo-qr-handler
Gives guests a QR for bookings and handles checkins

Listeo is a theme for Wordpress. This plugin enables the sending of QR codes for bookings and provides a means of handling check-ins at the booked events.

# Important
This code is being made available to Listeo users who have some programming knowledge as it will need
a little tweaking. It was created by Mark Jones but later modified by Chris Mask to perform some extra
functions, and he (Chris) splatted a bug as well.

It was created in response to several users saying they were desperate for this feature but when it was
delivered it turned out that Chris was the only one who really did need it and implemented it. 

Be prepared to have to look at the code and tweak if for your needs and if you do contact us to help you
get it going then please do so only if you really really need it.

# Installation
Download as a zip file and use the standard method of uploading a plugin. You may place using FTP if you prefer.

# Usage

	1. Add a page at /checkin
	2. Add the shortcode [rdmrj-event-checkin] to the page

# The Order Process

Listeo creates woocommerce products when listings for events are created and gives them the product category of "Listeo booking". All event listings are created like this.

When a guest makes a booking for an event a woocommerce order is created. The plugin generates a QR code from the guest's ID and the ID of the product and adds it to the emails that are sent to the guest and admin when the order is finalised.

# The Checkin Process

The guest presents their QR code to the person or people who are handling admission to the event. The QR code is scanned and will give a URL to the checkin page created according to the instructions above. The url will look something like this: 

https://yoursite.com/checkin/?check=mrtrClEjtpYkWyVqzfghrUA==

Visting this url will present a simple page that displays the name of the event, the user's login name, and the user's email address. There is a "Check in" button underneath this information which when clicked will check the user in. 

The checkin only happens once. When the button is clicked it is disabled. If the url is visited again (or the page refreshed in the browser) it will show a message that says the user is already checked-in, along with the event name and the guest's login and email.

# How it works

No extra information is added to the woocommerce orders. The plugin has everything it needs by using the Ids of the orders and guests. From these two pieces of information the unique QR codes are generated.

The plugin simply hooks into the woocommerce code where emails are created and adds the QR code.

The QR codes are never kept, every time they appear they are created on-the-fly using a Google service. A word about this. The Google service that creates QR codes was marked for deprecation in 2015. It should have died long ago but 6 years later in 2021 it's still responding to requests. I use this service to avoid dependencies on other PHP libraries but should it ever stop responding it can easily be replaced.

The plugin creates a very simple table in the database where it stores the ids of the user and events when a checkin is made. It uses this table to check that a QR code is used only once.

# Testing and Issues

This plugin is being released to the Listeo community because 20+ people expressed an interest (and my main project was boring so I broke off for a few hours and did this). It's very far from a fully functional event management plugin but it does the basic job of issuing a QR code to a guest and processing the checkin.

If you use this plugin and have any issues with it or requests for changes to how it works, use the Issues and Discussions areas of the Github repository to let me know and discuss them with me. 

If you do install the plugin please let me know. If you let me have your contact details I will add you to a list of those I will email if the plugin is updated. Alternatively, you can create a free Github account and track changes that way.

The code does require a few minor tidy-up changes at this point and will be done if the plugin is installed by users.

I will enable auto-update for the plugin if I am told that people are installing and using it.
