<?php

/*
|--------------------------------------------------------------------------
| Shareable shortlist message
|--------------------------------------------------------------------------
|
| What the client actually receives when the team sends them a link from
| /search — on WhatsApp, by email, by SMS, on Telegram, or through the phone's
| own share sheet. Every one of those carries this same text, so the client
| gets one message wherever it reaches them.
|
| Kept here rather than typed into the share modal's script so marketing can
| reword it without going near a view, and so there is one copy of it instead
| of one per share button.
|
| `:link` is replaced with the generated shortlist URL. Keep it alone on its
| own line with nothing before it: WhatsApp, Telegram and mail clients turn a
| bare URL into a tappable link, and are far less reliable about one sitting
| mid-sentence behind a label. Nothing may follow it on that line either.
|
| Asterisks are WhatsApp's bold markers; they show as plain asterisks in email
| and SMS, which is the trade of having a single message rather than one per
| channel.
|
*/

return [

    /*
    | Subject line for the email share. Ignored by every other channel.
    */
    'subject' => env('SHARE_LINK_SUBJECT', 'Welcome to Brand Adda'),

    'message' => <<<'TEXT'
Hi There!

Welcome to Brand Adda!

We’re excited to have you onboard and look forward to helping you plan smarter *Outdoor Advertising campaigns.*

*Here’s what you can do with Brand Adda:*

📍 Discover Outdoor Media
Explore hoardings and outdoor media opportunities across multiple locations.

🔎 Search & Compare
Find the right media based on location, size, format, pricing and availability.

📊 Plan Your Campaign
Create your OOH media plan quickly and efficiently from one platform.

🤝 Simplify Execution
From media discovery to campaign execution, Brand Adda helps make the process easier and more transparent.

🚀 Plan Smarter. Reach Better.
Access a wider OOH ecosystem and make more informed outdoor media decisions.

👉 Log in using your registered email and start exploring Outdoor Media on Brand Adda.

:link

*Brand Adda*
The Smarter Way to Outdoor Advertising
TEXT,

];
