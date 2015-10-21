# Orignal Work

I'd like to give credit where it is due. The source of this project is some great work by Nicola Asuni that proved super-easy to use. You can 
support that project @ http://sourceforge.net/donate/index.php?group_id=128076 or via PayPal at paypal@tecnick.com

Here are links to the original work:

- http://www.tcpdf.org
- http://www.sourceforge.net/projects/tcpdf
- https://github.com/tecnickcom/TCPDF

You can read the full original README [here](README_TCPDF.TXT).

# What this does

This port of Nicola's project focuses on 2 things

* Generating fancy (rounded corner) QR codes
* Generating PDF417 Barcodes
* Providing a super-easy PHP mini-app that does these things (and more to come) within your application framework and outputs images that can be included in HTML img tags (see examples below)

# Requirements 

* Not extensively tested (yet)!
* PHP >= 5.4 with GD and ImagMagick (http://www.imagemagick.org/script/formats.php)
* TCPDF library (included)

# Format
* All images generated will be PNG24 with transparency.

# Usage

You should be able to drop in this mini-app on a PHP enabled server and have it ready to go. 

## Parameters

## Case 1: Generating barcodes in-line with HTML 

Let's say, you have the following data packet you would like to include in your HTML page as a PDF417 or Fancy QR Code (rounded corners, more pleasant to the eyes :)):

    {
        payload: "I'm here!",
        size: 8,
        rgb: "50,50,50"
    }

You can very easily, with a GET call, build a URL that looks something like this, and will automatically generate the QR / PDF417 image in your HTML code:

    <img src="{your_app_root}/?size=8&rgb=50,50,50&payload=I'm here" />

An example of this @ work is below: 

PDF417:

    <img src="http://com-uchechilaka-docfactory.appspot.com/?data=Hello%20World&size=6&type=PDF417" />

![Example PDF 417](https://com-uchechilaka-docfactory.appspot.com/?data=Hello%20World&size=10&type=PDF417)

You can preview this @ work [in your browser](https://com-uchechilaka-docfactory.appspot.com/?data=Hello%20World&size=10&type=PDF417)

Fancy QR:

    <img src="http://com-uchechilaka-docfactory.appspot.com/?type=FANCYQR&size=8&rgb=50,50,50&payload=I'm here" />

![Example Fancy QR](http://com-uchechilaka-docfactory.appspot.com/?type=FANCYQR&size=8&rgb=50,50,50&payload=I'm here)

You can preview this @ work <a href="https://com-uchechilaka-docfactory.appspot.com/?data=Hello%20World&size=10&type=FANCYQR" target="_blank">in your browser</a>

# Secure Access to Demo API

Thanks to the great folks @ Google, you get SSL encryption out of the box with Google Cloud projects! (Seriously, we all should try that out at least once. I took a stab 
@ AWS about a year back, and it felt super-sticky with that. GCloud feels breezy - if you've been to AWS recently and disagree with that opinion based on Google's 
AppEngine offering for super-light apps... you're entitled to that opinion! :) ). 

So... that means you can also run the PDF 417 via HTTPS as seen below:

    <img src="https://com-uchechilaka-docfactory.appspot.com/?data=Hello%20World&size=6&type=PDF417" />

![Example PDF 417 (HTTPS)](https://com-uchechilaka-docfactory.appspot.com/?data=Hello%20World&size=10&type=PDF417)

# The Future

Driverless cars!! ...and Teleporting... anyone? As far as this library goes - yes, there will be updates. The included 
TCPDF library is worth a look for use in generating your PDF docs for your application and have all that reside within your implementation of the docfactory 
project. This is an example of a (super limited) use of the library to provide QR and PDF417 web capabilities. As I include new functionality for use 
in my app projects, you'll get them here too.

Cheers!


