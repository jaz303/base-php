<?php
class XML_RPC
{
    /**
     * Returns <var>true</var> if <var>$xml</var> is a <var>SimpleXML</var> object
     * representing an XML-RPC request.
     *
     * @return true if $xml represents an XML-RPC request, false otherwise.
     */
    public static function is_request($xml) {
        return is_object($xml) && $xml->getName() == 'methodCall';
    }
}

class XML_RPC_Request
{
    
}

class XML_RPC_Response
{
    
}
?>