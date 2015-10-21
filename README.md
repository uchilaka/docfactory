# Orignal Work

I'd like to give credit where it is due. The source of this project is some great work by Nicola Asuni that proved super-easy to use. You can 
support that project @ http://sourceforge.net/donate/index.php?group_id=128076 or via PayPal at paypal@tecnick.com

# What this does

This port of Nicola's project focuses on 2 things

* Generating fancy (rounded corner) QR codes
* Generating PDF417 Barcodes
* Providing a super-easy PHP mini-app that does these things (and more to come) within your application framework and outputs images that can be included in HTML img tags (see examples below)

# Requirements 

* Not extensively tested (yet)!
* PHP >= 5.4
* TCPDF library (included)

# Usage

## Case 1: Generating barcodes in-line with HTML 

Let's say, you have the following data packet you would like to include in your HTML page:

    {
        payload: "I'm here!",
        size: 8,
        rgb: "50,50,50"
    }

You can very easily, with a GET call, build a URL that looks something like this, and will automatically generate the QR / PDF417 image in your HTML code:

    <img src="{your_app_root}/?size=8&rgb=50,50,50&payload=I'm here" />


