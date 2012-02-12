==============
jquery-wysiwym
==============

This is a script that will convert a standard textarea into a wysiwym editor
similar to what StackOverflow.com offers.


REQUIREMENTS
------------
* jquery-1.4.4.js or higher.


INSTALL
-------
1.) Copy the 'wysiwym' directory to the media location in your site directory.

2.) Include wysiwym.js, markdown.js and wysiwym.css to your site header.
    <script type='text/javascript' src='/media/js/wysiwym/wysiwym.js'></script>
    <script type='text/javascript' src='/media/js/wysiwym/markdown.js'></script>
    <link type='text/css' rel='stylesheet' href='/media/js/wysiwym/wysiwym.css'/>

3.) Hook in the script by calling .wysiwym() on the textarea element
    in your page. That's It!:
    $('#mytextarea').wysiwym(WysiwymMarkdown);
    $('#mytextarea').wysiwym(WysiwymMarkdown, {options});


OPTIONS
-------
The options argument to wysiwym is a Javascript object that knows about the
following attributes:

containerButton - jQuery element to place buttons; Default: undefined (auto-created).
containerHelp   - jQuery element to place help; Default: undefined (auto-created).
helpEnabled     - Set false to disable the help menu; Default: true.
helpToggle      - Set false to always show the help menu (disable toggle); Default: true.
helpToggleElem  - jQuery element to toggle help (makes <a> by default)
helpTextShow    - Toggle text to display when help is not visible; Default: 'show markup syntax'.
helpTextHide    - Toggle text to display when help is visible;  Default: 'hide markup syntax'.
