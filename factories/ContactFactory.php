<?php

namespace rapidweb\googlecontacts\factories;

use rapidweb\googlecontacts\helpers\GoogleHelper;
use rapidweb\googlecontacts\objects\Contact;

abstract class ContactFactory
{
    public static function getAll()
    {
        $client = GoogleHelper::getClient();

        $req = new \Google_Http_Request('https://www.google.com/m8/feeds/contacts/default/full?max-results=10000&updated-min=2007-03-16T00:00:00');

        $val = $client->getAuth()->authenticatedRequest($req);

        $response = $val->getResponseBody();

        $xmlContacts = simplexml_load_string($response);
        $xmlContacts->registerXPathNamespace('gd', 'http://schemas.google.com/g/2005');

        foreach ($xmlContacts->entry as $xmlContactsEntry) {
            $contactDetails = array();

            $contactDetails['id'] = (string) $xmlContactsEntry->id;
            $contactDetails['name'] = (string) $xmlContactsEntry->title;

            foreach ($xmlContactsEntry->children() as $key => $value) {
                $attributes = $value->attributes();

                if ($key == 'link') {
                    if ($attributes['rel'] == 'edit') {
                        $contactDetails['editURL'] = (string) $attributes['href'];
                    } elseif ($attributes['rel'] == 'self') {
                        $contactDetails['selfURL'] = (string) $attributes['href'];
                    }
                }
            }

            $contactGDNodes = $xmlContactsEntry->children('http://schemas.google.com/g/2005');

            foreach ($contactGDNodes as $key => $value) {
                $attributes = $value->attributes();

                if ($key == 'email') {
                    $contactDetails[$key] = (string) $attributes['address'];
                } else {
                    $contactDetails[$key] = (string) $value;
                }
            }

            $contactsArray[] = new Contact($contactDetails);
        }

        return $contactsArray;
    }

    public static function getBySelfURL($selfURL)
    {
        $client = GoogleHelper::getClient();

        $req = new \Google_Http_Request($selfURL);

        $val = $client->getAuth()->authenticatedRequest($req);

        $response = $val->getResponseBody();

        $xmlContact = simplexml_load_string($response);
        $xmlContact->registerXPathNamespace('gd', 'http://schemas.google.com/g/2005');

        $xmlContactsEntry = $xmlContact;

        $contactDetails = array();

        $contactDetails['id'] = (string) $xmlContactsEntry->id;
        $contactDetails['name'] = (string) $xmlContactsEntry->title;

        foreach ($xmlContactsEntry->children() as $key => $value) {
            $attributes = $value->attributes();

            if ($key == 'link') {
                if ($attributes['rel'] == 'edit') {
                    $contactDetails['editURL'] = (string) $attributes['href'];
                } elseif ($attributes['rel'] == 'self') {
                    $contactDetails['selfURL'] = (string) $attributes['href'];
                }
            }
        }

        $contactGDNodes = $xmlContactsEntry->children('http://schemas.google.com/g/2005');

        foreach ($contactGDNodes as $key => $value) {
            $attributes = $value->attributes();

            if ($key == 'email') {
                $contactDetails[$key] = (string) $attributes['address'];
            } else {
                $contactDetails[$key] = (string) $value;
            }
        }

        return new Contact($contactDetails);
    }

    public static function submitUpdates(Contact $updatedContact)
    {
        $client = GoogleHelper::getClient();

        $req = new \Google_Http_Request($updatedContact->selfURL);

        $val = $client->getAuth()->authenticatedRequest($req);

        $response = $val->getResponseBody();

        $xmlContact = simplexml_load_string($response);
        $xmlContact->registerXPathNamespace('gd', 'http://schemas.google.com/g/2005');

        $xmlContactsEntry = $xmlContact;

        $xmlContactsEntry->title = $updatedContact->name;

        $contactGDNodes = $xmlContactsEntry->children('http://schemas.google.com/g/2005');

        foreach ($contactGDNodes as $key => $value) {
            $attributes = $value->attributes();

            if ($key == 'email') {
                $attributes['address'] = $updatedContact->email;
            } else {
                $xmlContactsEntry->$key = $updatedContact->$key;
                $attributes['uri'] = '';
            }
        }

        $updatedXML = $xmlContactsEntry->asXML();

        $req = new \Google_Http_Request($updatedContact->editURL);
        $req->setRequestHeaders(array('content-type' => 'application/atom+xml; charset=UTF-8; type=feed'));
        $req->setRequestMethod('PUT');
        $req->setPostBody($updatedXML);

        $val = $client->getAuth()->authenticatedRequest($req);

        $response = $val->getResponseBody();

        $xmlContact = simplexml_load_string($response);
        $xmlContact->registerXPathNamespace('gd', 'http://schemas.google.com/g/2005');

        $xmlContactsEntry = $xmlContact;

        $contactDetails = array();

        $contactDetails['id'] = (string) $xmlContactsEntry->id;
        $contactDetails['name'] = (string) $xmlContactsEntry->title;

        foreach ($xmlContactsEntry->children() as $key => $value) {
            $attributes = $value->attributes();

            if ($key == 'link') {
                if ($attributes['rel'] == 'edit') {
                    $contactDetails['editURL'] = (string) $attributes['href'];
                } elseif ($attributes['rel'] == 'self') {
                    $contactDetails['selfURL'] = (string) $attributes['href'];
                }
            }
        }

        $contactGDNodes = $xmlContactsEntry->children('http://schemas.google.com/g/2005');

        foreach ($contactGDNodes as $key => $value) {
            $attributes = $value->attributes();

            if ($key == 'email') {
                $contactDetails[$key] = (string) $attributes['address'];
            } else {
                $contactDetails[$key] = (string) $value;
            }
        }

        return new Contact($contactDetails);
    }
		
		public static function xml2array($xml)
		{
		    $arr = array();
 
		    foreach ($xml->children() as $r)
		    {
		        $t = array();
		        if(count($r->children()) == 0)
		        {
		            $arr[$r->getName()] = strval($r);
		        }
		        else
		        {
		            $arr[$r->getName()][] = xml2array($r);
		        }
		    }
		    return $arr;
		}

    public static function create($name, $phoneNumber, $emailAddress)
    {
        $doc = new \DOMDocument();
        $doc->formatOutput = true;
        $entry = $doc->createElement('atom:entry');
        $entry->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:atom', 'http://www.w3.org/2005/Atom');
        $entry->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:gd', 'http://schemas.google.com/g/2005');
        $entry->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:gd', 'http://schemas.google.com/g/2005');
        $doc->appendChild($entry);

        $title = $doc->createElement('title', $name);
        $entry->appendChild($title);
				
        $content = $doc->createElement('content', 'some content right here');
        $content->setAttribute('rel', 'http://schemas.google.com/g/2005#work');
        $entry->appendChild($content);

        $email = $doc->createElement('gd:email');
        $email->setAttribute('rel', 'http://schemas.google.com/g/2005#work');
        $email->setAttribute('address', $emailAddress);
        $entry->appendChild($email);

        $contact = $doc->createElement('gd:phoneNumber', $phoneNumber);
        $contact->setAttribute('rel', 'http://schemas.google.com/g/2005#work');
        $entry->appendChild($contact);
				
        $industry = $doc->createElement('gd:extendedProperty');
        $industry->setAttribute('name', 'industry');
        $industry->setAttribute('value', 'coolInc');
        $industry->setAttribute('rel', 'http://schemas.google.com/g/2005#work');
        $entry->appendChild($industry);
				
        $xmlToSend = $doc->saveXML();

        $client = GoogleHelper::getClient();

        $req = new \Google_Http_Request('https://www.google.com/m8/feeds/contacts/default/full');
        $req->setRequestHeaders(array('content-type' => 'application/atom+xml; charset=UTF-8; type=feed'));
        $req->setRequestMethod('POST');
        $req->setPostBody($xmlToSend);

        $val = $client->getAuth()->authenticatedRequest($req);

        $response = $val->getResponseBody();

        $xmlContact = simplexml_load_string($response);
        $xmlContact->registerXPathNamespace('gd', 'http://schemas.google.com/g/2005');
				
				// $contactData = xml2array($xmlContact);
				error_log( var_export($response, true) );

        $xmlContactsEntry = $xmlContact;

        $contactDetails = array();

        $contactDetails['id'] = (string) $xmlContactsEntry->id;
        $contactDetails['name'] = (string) $xmlContactsEntry->title;

        foreach ($xmlContactsEntry->children() as $key => $value) {
            $attributes = $value->attributes();

            if ($key == 'link') {
                if ($attributes['rel'] == 'edit') {
                    $contactDetails['editURL'] = (string) $attributes['href'];
                } elseif ($attributes['rel'] == 'self') {
                    $contactDetails['selfURL'] = (string) $attributes['href'];
                }
            }
        }

        $contactGDNodes = $xmlContactsEntry->children('http://schemas.google.com/g/2005');

        foreach ($contactGDNodes as $key => $value) {
            $attributes = $value->attributes();

            if ($key == 'email') {
                $contactDetails[$key] = (string) $attributes['address'];
            } else {
                $contactDetails[$key] = (string) $value;
            }
        }

        return new Contact($contactDetails);
    }
}
