<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */
 
// the following functions are deprecated
 
// --------------------------------- convert_html -------------------------------
// function: convert_html()
// input: text to be translated
// output: translated text without special characters

// description:
// function returns text but changing all the special characters to their html escaped aquivalent

function convert_html ($text)
{
  // special characters and their html escaped aquivalents
  $char["�"]="&iexcl;";
  $char["�"]="&cent;";
  $char["�"]="&pound;";
  $char["�"]="&curren;";
  $char["�"]="&yen;";
  $char["�"]="&brvbar;";
  $char["�"]="&sect;";
  $char["�"]="&uml;";
  $char["�"]="&copy;";
  $char["�"]="&ordf;";
  $char["�"]="&laquo;";
  $char["�"]="&not;";
  $char["\x7f"]="&shy;";
  $char["�"]="&reg;";
  $char["�"]="&macr;";
  $char["�"]="&deg;";
  $char["�"]="&plusmn;";
  $char["�"]="&sup2;";
  $char["�"]="&sup3;";
  $char["�"]="&acute;";
  $char["�"]="&micro;";
  $char["�"]="&para;";
  $char["�"]="&middot;";
  $char["�"]="&cedil;";
  $char["�"]="&sup1;";
  $char["�"]="&ordm;";
  $char["�"]="&raquo;";
  $char["�"]="&frac14;";
  $char["�"]="&frac12;";
  $char["�"]="&frac34;";
  $char["�"]="&iquest;";
  $char["�"]="&Agrave;";
  $char["�"]="&Aacute;";
  $char["�"]="&Acirc;";
  $char["�"]="&Atilde;";
  $char["�"]="&Auml;";
  $char["�"]="&Aring;";
  $char["�"]="&AElig;";
  $char["�"]="&Ccedil;";
  $char["�"]="&Egrave;";
  $char["�"]="&Eacute;";
  $char["�"]="&Ecirc;";
  $char["�"]="&Euml;";
  $char["�"]="&Igrave;";
  $char["�"]="&Iacute;";
  $char["�"]="&Icirc;";
  $char["�"]="&Iuml;";
  $char["�"]="&ETH;";
  $char["�"]="&Ntilde;";
  $char["�"]="&Ograve;";
  $char["�"]="&Oacute;";
  $char["�"]="&Ocirc;";
  $char["�"]="&Otilde;";
  $char["�"]="&Ouml;";
  $char["�"]="&times;";
  $char["�"]="&Oslash;";
  $char["�"]="&Ugrave;";
  $char["�"]="&Uacute;";
  $char["�"]="&Ucirc;";
  $char["�"]="&Uuml;";
  $char["�"]="&Yacute;";
  $char["�"]="&THORN;";
  $char["�"]="&szlig;";
  $char["�"]="&agrave;";
  $char["�"]="&aacute;";
  $char["�"]="&acirc;";
  $char["�"]="&atilde;";
  $char["�"]="&auml;";
  $char["�"]="&aring;";
  $char["�"]="&aelig;";
  $char["�"]="&ccedil;";
  $char["�"]="&egrave;";
  $char["�"]="&eacute;";
  $char["�"]="&ecirc;";
  $char["�"]="&euml;";
  $char["�"]="&igrave;";
  $char["�"]="&iacute;";
  $char["�"]="&icirc;";
  $char["�"]="&iuml;";
  $char["�"]="&ieth;";
  $char["�"]="&ntilde;";
  $char["�"]="&ograve;";
  $char["�"]="&oacute;";
  $char["�"]="&ocirc;";
  $char["�"]="&otilde;";
  $char["�"]="&ouml;";
  $char["�"]="&divide;";
  $char["�"]="&oslash;";
  $char["�"]="&ugrave;";
  $char["�"]="&uacute;";
  $char["�"]="&ucirc;";
  $char["�"]="&uuml;";
  $char["�"]="&yacute;";
  $char["�"]="&thorn;";
  $char["�"]="&yuml;";

  // translate all
  $text_new = strtr ($text, $char);

  return $text_new;
}

// --------------------------------- convert_unicode -------------------------------
// function: convert_unicode()
// input: text to be translated
// output: translated text without special characters

// description:
// function returns text but changing all the special chars to their unicode aquivalent

function convert_unicode ($text)
{
  // ISO 8859-1 special characters and their aquivalent unicode
  $char["�"] = "&#X160;";
  $char["�"] = "&#X161;";
  $char["�"] = "&#X162;";
  $char["�"] = "&#X163;";
  $char["�"] = "&#X164;";
  $char["�"] = "&#X165;";
  $char["�"] = "&#X166;";
  $char["�"] = "&#X167;";
  $char["�"] = "&#X168;";
  $char["�"] = "&#X169;";
  $char["�"] = "&#X170;";
  $char["�"] = "&#X171;";
  $char["�"] = "&#X172;";
  $char["&#173;"] = "&#X173;";
  $char["�"] = "&#X174;";
  $char["�"] = "&#X175;";
  $char["�"] = "&#X176;";
  $char["�"] = "&#X177;";
  $char["�"] = "&#X178;";
  $char["�"] = "&#X179;";
  $char["�"] = "&#X180;";
  $char["�"] = "&#X181;";
  $char["�"] = "&#X182;";
  $char["�"] = "&#X183;";
  $char["�"] = "&#X184;";
  $char["�"] = "&#X185;";
  $char["�"] = "&#X186;";
  $char["�"] = "&#X187;";
  $char["�"] = "&#X188;";
  $char["�"] = "&#X189;";
  $char["�"] = "&#X190;";
  $char["+"] = "&#X191;";
  $char["�"] = "&#X192;";
  $char["-"] = "&#X193;";
  $char["�"] = "&#X194;";
  $char["�"] = "&#X195;";
  $char["�"] = "&#X196;";
  $char["�"] = "&#X197;";
  $char["�"] = "&#X198;";
  $char["�"] = "&#X199;";
  $char["�"] = "&#X200;";
  $char["�"] = "&#X201;";
  $char["�"] = "&#X202;";
  $char["�"] = "&#X203;";
  $char["�"] = "&#X204;";
  $char["�"] = "&#X205;";
  $char["�"] = "&#X206;";
  $char["�"] = "&#X207;";
  $char["�"] = "&#X208;";
  $char["�"] = "&#X209;";
  $char["�"] = "&#X210;";
  $char["�"] = "&#X211;";
  $char["�"] = "&#X212;";
  $char["�"] = "&#X213;";
  $char["�"] = "&#X214;";
  $char["�"] = "&#X215;";
  $char["�"] = "&#X216;";
  $char["�"] = "&#X217;";
  $char["�"] = "&#X218;";
  $char["�"] = "&#X219;";
  $char["�"] = "&#X220;";
  $char["�"] = "&#X221;";
  $char["�"] = "&#X222;";
  $char["�"] = "&#X223;";
  $char["�"] = "&#X224;";
  $char["�"] = "&#X225;";
  $char["�"] = "&#X226;";
  $char["�"] = "&#X227;";
  $char["�"] = "&#X228;";
  $char["�"] = "&#X229;";
  $char["�"] = "&#X230;";
  $char["�"] = "&#X231;";
  $char["�"] = "&#X232;";
  $char["�"] = "&#X233;";
  $char["�"] = "&#X234;";
  $char["�"] = "&#X235;";
  $char["�"] = "&#X236;";
  $char["�"] = "&#X237;";
  $char["�"] = "&#X238;";
  $char["�"] = "&#X239;";
  $char["�"] = "&#X240;";
  $char["�"] = "&#X241;";
  $char["�"] = "&#X242;";
  $char["�"] = "&#X243;";
  $char["�"] = "&#X244;";
  $char["�"] = "&#X245;";
  $char["�"] = "&#X246;";
  $char["�"] = "&#X247;";
  $char["�"] = "&#X248;";
  $char["�"] = "&#X249;";
  $char["�"] = "&#X250;";
  $char["�"] = "&#X251;";
  $char["�"] = "&#X252;";
  $char["�"] = "&#X253;";
  $char["�"] = "&#X254;";
  $char["�"] = "&#X255;";

  // translate all
  $text_new = strtr ($text, $char);

  return $text_new;
}

// --------------------------------- deconvert_html -------------------------------
// function: deconvert_html()
// input: text to be translated
// output: translated text with special characters

// description:
// function returns text but changing all the html escaped aquivalent to the special characters

function deconvert_html ($text)
{
  // special characters and their html escaped aquivalents
  // list of transformations
  $simb["&iexcl;"]="�";
  $simb["&cent;"]="�";
  $simb["&pound;"]="�";
  $simb["&curren;"]="�";
  $simb["&yen;"]="�";
  $simb["&brvbar;"]="�";
  $simb["&sect;"]="�";
  $simb["&uml;"]="�";
  $simb["�"]="&copy;";
  $simb["�"]="&ordf;";
  $simb["�"]="&laquo;";
  $simb["�"]="&not;";
  $simb["\x7f"]="&shy;";
  $simb["�"]="&reg;";
  $simb["�"]="&macr;";
  $simb["�"]="&deg;";
  $simb["�"]="&plusmn;";
  $simb["�"]="&sup2;";
  $simb["�"]="&sup3;";
  $simb["�"]="&acute;";
  $simb["�"]="&micro;";
  $simb["�"]="&para;";
  $simb["�"]="&middot;";
  $simb["�"]="&cedil;";
  $simb["�"]="&sup1;";
  $simb["�"]="&ordm;";
  $simb["�"]="&raquo;";
  $simb["�"]="&frac14;";
  $simb["�"]="&frac12;";
  $simb["�"]="&frac34;";
  $simb["�"]="&iquest;";
  $simb["�"]="&Agrave;";
  $simb["�"]="&Aacute;";
  $simb["�"]="&Acirc;";
  $simb["�"]="&Atilde;";
  $simb["�"]="&Auml;";
  $simb["�"]="&Aring;";
  $simb["�"]="&AElig;";
  $simb["�"]="&Ccedil;";
  $simb["�"]="&Egrave;";
  $simb["�"]="&Eacute;";
  $simb["�"]="&Ecirc;";
  $simb["�"]="&Euml;";
  $simb["�"]="&Igrave;";
  $simb["�"]="&Iacute;";
  $simb["�"]="&Icirc;";
  $simb["�"]="&Iuml;";
  $simb["�"]="&ETH;";
  $simb["�"]="&Ntilde;";
  $simb["�"]="&Ograve;";
  $simb["�"]="&Oacute;";
  $simb["�"]="&Ocirc;";
  $simb["�"]="&Otilde;";
  $simb["�"]="&Ouml;";
  $simb["�"]="&times;";
  $simb["�"]="&Oslash;";
  $simb["�"]="&Ugrave;";
  $simb["�"]="&Uacute;";
  $simb["�"]="&Ucirc;";
  $simb["�"]="&Uuml;";
  $simb["�"]="&Yacute;";
  $simb["�"]="&THORN;";
  $simb["�"]="&szlig;";
  $simb["�"]="&agrave;";
  $simb["�"]="&aacute;";
  $simb["�"]="&acirc;";
  $simb["�"]="&atilde;";
  $simb["�"]="&auml;";
  $simb["�"]="&aring;";
  $simb["�"]="&aelig;";
  $simb["�"]="&ccedil;";
  $simb["�"]="&egrave;";
  $simb["�"]="&eacute;";
  $simb["�"]="&ecirc;";
  $simb["�"]="&euml;";
  $simb["�"]="&igrave;";
  $simb["�"]="&iacute;";
  $simb["�"]="&icirc;";
  $simb["�"]="&iuml;";
  $simb["�"]="&ieth;";
  $simb["�"]="&ntilde;";
  $simb["�"]="&ograve;";
  $simb["�"]="&oacute;";
  $simb["�"]="&ocirc;";
  $simb["�"]="&otilde;";
  $simb["�"]="&ouml;";
  $simb["�"]="&divide;";
  $simb["�"]="&oslash;";
  $simb["�"]="&ugrave;";
  $simb["�"]="&uacute;";
  $simb["�"]="&ucirc;";
  $simb["�"]="&uuml;";
  $simb["�"]="&yacute;";
  $simb["�"]="&thorn;";
  $simb["�"]="&yuml;";

  // translate all
  $text_new = strtr ($text, $char);

  return $text_new;
}

// --------------------------------- deconvert_unicode -------------------------------
// function: deconvert_unicode()
// input: text to be translated
// output: translated text without special characters

// description:
// function returns text but changing the unicode aquivalent to the special character

function deconvert_unicode ($text)
{
  // ISO 8859-1 special characters and their aquivalent unicode
  $char["&#X160;"] = "�";
  $char["&#X161;"] = "�";
  $char["&#X162;"] = "�";
  $char["&#X163;"] = "�";
  $char["&#X164;"] = "�";
  $char["&#X165;"] = "�";
  $char["&#X166;"] = "�";
  $char["&#X167;"] = "�";
  $char["&#X168;"] = "�";
  $char["&#X169;"] = "�";
  $char["&#X170;"] = "�";
  $char["&#X171;"] = "�";
  $char["&#X172;"] = "�";
  $char["&#X173;"] = "&#173;";
  $char["&#X174;"] = "�";
  $char["&#X175;"] = "�";
  $char["&#X176;"] = "�";
  $char["&#X177;"] = "�";
  $char["&#X178;"] = "�";
  $char["&#X179;"] = "�";
  $char["&#X180;"] = "�";
  $char["&#X181;"] = "�";
  $char["&#X182;"] = "�";
  $char["&#X183;"] = "�";
  $char["&#X184;"] = "�";
  $char["&#X185;"] = "�";
  $char["&#X186;"] = "�";
  $char["&#X187;"] = "�";
  $char["&#X188;"] = "�";
  $char["&#X189;"] = "�";
  $char["&#X190;"] = "�";
  $char["&#X191;"] = "+";
  $char["&#X192;"] = "�";
  $char["&#X193;"] = "-";
  $char["&#X194;"] = "�";
  $char["&#X195;"] = "�";
  $char["&#X196;"] = "�";
  $char["&#X197;"] = "�";
  $char["&#X198;"] = "�";
  $char["&#X199;"] = "�";
  $char["&#X200;"] = "�";
  $char["&#X201;"] = "�";
  $char["&#X202;"] = "�";
  $char["&#X203;"] = "�";
  $char["&#X204;"] = "�";
  $char["&#X205;"] = "�";
  $char["&#X206;"] = "�";
  $char["&#X207;"] = "�";
  $char["&#X208;"] = "�";
  $char["&#X209;"] = "�";
  $char["&#X210;"] = "�";
  $char["&#X211;"] = "�";
  $char["&#X212;"] = "�";
  $char["&#X213;"] = "�";
  $char["&#X214;"] = "�";
  $char["&#X215;"] = "�";
  $char["&#X216;"] = "�";
  $char["&#X217;"] = "�";
  $char["&#X218;"] = "�";
  $char["&#X219;"] = "�";
  $char["&#X220;"] = "�";
  $char["&#X221;"] = "�";
  $char["&#X222;"] = "�";
  $char["&#X223;"] = "�";
  $char["&#X224;"] = "�";
  $char["&#X225;"] = "�";
  $char["&#X226;"] = "�";
  $char["&#X227;"] = "�";
  $char["&#X228;"] = "�";
  $char["&#X229;"] = "�";
  $char["&#X230;"] = "�";
  $char["&#X231;"] = "�";
  $char["&#X232;"] = "�";
  $char["&#X233;"] = "�";
  $char["&#X234;"] = "�";
  $char["&#X235;"] = "�";
  $char["&#X236;"] = "�";
  $char["&#X237;"] = "�";
  $char["&#X238;"] = "�";
  $char["&#X239;"] = "�";
  $char["&#X240;"] = "�";
  $char["&#X241;"] = "�";
  $char["&#X242;"] = "�";
  $char["&#X243;"] = "�";
  $char["&#X244;"] = "�";
  $char["&#X245;"] = "�";
  $char["&#X246;"] = "�";
  $char["&#X247;"] = "�";
  $char["&#X248;"] = "�";
  $char["&#X249;"] = "�";
  $char["&#X250;"] = "�";
  $char["&#X251;"] = "�";
  $char["&#X252;"] = "�";
  $char["&#X253;"] = "�";
  $char["&#X254;"] = "�";
  $char["&#X255;"] = "�";

  // translate all
  $text_new = strtr ($text, $char);

  return $text_new;
}
?>