<?php
/**
 * Class for simplifying HTTP requests by wrapping cURL workflow.
 * 
 */
class SimpleHTTPClient {
    /**
     * HTTP response status; will contain associative array representing
     * the HTTP version, status code, and reason phrase.
     */
    private $responseStatus = null;

    /**
     * HTTP response header; will contain associative array of header
     * attributes returned from the cURL request.
     */
    private $responseHeader = null;

    /**
     * HTTP response body; will contain a string representing the body
     * of the response returned from the cURL request.
     */
    private $responseBody = null;

    /**
     * Make an HTTP request.  Defaults to a simple GET request if only
     * the $url parameter is specified.  Returns the complete response
     * header and body in a PHP-friendly data structure.
     * 
     * @param String $url: A complete URL including URL parameters.
     * @param String $requestMethod: The HTTP request method to use for this request.
     * @param String $requestBody: The striing literal containing request body data (eg. POST params go here).
     * @return Array: Associative array containing response header and body as 'header' and 'body' keys.
     */
    function makeRequest($url, $requestMethod, $requestBody = null, $requestHeader = null) {
        // Reinitialize response header and body.
        $this->responseHeader = null;
        $this->responseBody = null;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($requestHeader !== null) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeader);
        }
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'handleResponseHeader'));
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, array($this, 'handleResponseBody'));

        // Additional options need to be set for PUT and POST requests.
        if ($requestMethod == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        } else if ($requestMethod == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        }

        // Execute and close the request and close the connection
        // handler as quickly as possible.
        $response = curl_exec($ch);
        curl_close($ch);

        return array(
            'status' => $this->responseStatus,
            'header' => $this->responseHeader,
            'body' => $this->responseBody,
        );
    }

    /**
     * Process an incoming response header following a cURL request and
     * store the header in $this->responseHeader.
     * 
     * @param Object $ch: The cURL handler instance.
     * @param String $headerData: The header to handle; expects header to come in one line at a time.
     * @return Int: The length of the input data.
     */
    private function handleResponseHeader($ch, $headerData) {
        // If we haven't found the HTTP status yet, then try to match it.
        if ($this->responseStatus == null) {
            $regex = '/^\s*HTTP\s*\/\s*(?P<protocolVersion>\d*\.\d*)\s*(?P<statusCode>\d*)\s(?P<reasonPhrase>.*)\r\n/';
            preg_match($regex , $headerData, $matches);

            foreach (array('protocolVersion', 'statusCode', 'reasonPhrase') as $part) {
                if (isset($matches[$part])) {
                    $this->responseStatus[$part] = $matches[$part];
                }
            }
        }

        // Digest HTTP header attributes.
        if (!isset($responseStatusMatches) || empty($responseStatusMatches)) {
            $regex = '/^\s*(?P<attributeName>[a-zA-Z0-9-]*):\s*(?P<attributeValue>.*)\r\n/';
            preg_match($regex, $headerData, $matches);

            if (isset($matches['attributeName'])) {
                $this->responseHeader[$matches['attributeName']] = isset($matches['attributeValue']) ? $matches['attributeValue'] : null;
            }
        }

        return strlen($headerData);
    }

    /**
     * Process an incoming response body following a cURL request
     * and store the body in $this->responseBody.
     * 
     * @param Object $ch: The cURL handler instance.
     * @param String $bodyData: The body data to handle.
     * @param Int: The length of the input data.
     */
    private function handleResponseBody($ch, $bodyData) {
        $this->responseBody .= $bodyData;

        return strlen($bodyData);
    }
}