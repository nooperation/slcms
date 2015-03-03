// Server data
string URL_REGISTER = "http://localhost:1942/simstats/action/registerServer.php";
string URL_UPDATE = "http://localhost:1942/simstats/action/updateServer.php";
key registerRequestId;
key urlRequestId; 
key listenKey;
string assignedUrl = "";

string CONFIG_PATH = "Config";
integer currentConfigLine = 0;
key configQueryId = NULL_KEY;
string authToken = "";



integer ProcessRequest(list pathParts)
{
	string firstPathPart = llList2String(requestedPathParts, 0);
	
	if(firstPathPart == "test")
	{
		llHTTPResponse(requestId, 200, "Hi!");
		return TRUE;
	}
	
	return FALSE;
}

/// <summary>
/// 
/// </summary>
/// <param name="message">Message to output</param>
Output(string message)
{
    //llInstantMessage(llGetOwner(), message);
    llOwnerSay(message);
}

string ExtractValueFromQuery(string query, string name)
{
    list queryParts = llParseString2List(query, ["&"], []);
    integer numQueryParts = llGetListLength(queryParts);
    integer i;

    for(i = 0; i < numQueryParts; i++)
    {
        list keyValuePair = llParseString2List(llList2String(queryParts, i), ["="], []);
        if(llGetListLength(keyValuePair) == 2)
        {
            if(llList2String(keyValuePair, 0) == name)
            {
                return llList2String(keyValuePair, 1);   
            }
        }
    }
    
    return "";
}

/// <summary>
/// Processes a single line from the settings file
/// Each line must be in the format of: setting name = value
/// </summary>
/// <param name="line">Raw line from settnigs file</param>
processTriggerLine(string line)
{
    integer seperatorIndex = llSubStringIndex(line, "=");
    string name;
    string value;
    
    if(seperatorIndex <= 0)
    {
        Output("Missing separator: " + line);
        return;
    }

    name = llToLower(llStringTrim(llGetSubString(line, 0, seperatorIndex - 1), STRING_TRIM_TAIL));
    value = llStringTrim(llGetSubString(line, seperatorIndex + 1, -1), STRING_TRIM);
    
    if(name == "authtoken")
    {
        authToken = value;
        Output("AuthToken = " + authToken);
    }
}

/// <summary>
/// Handles processing of a single line of our actions file.
/// </summary>
/// <param name="line">Line from actions notecard</param>
processConfigLine(string line)
{    
    if(line == EOF)
    {
        state default;
        return;
    }
    
    line = llStringTrim(line, STRING_TRIM_HEAD);
    
    if(line == "" || llGetSubString(line, 0, 0) == "#")
    {
        configQueryId = llGetNotecardLine(CONFIG_PATH, ++currentConfigLine);
        return;
    }
    
    processTriggerLine(line);

    configQueryId = llGetNotecardLine(CONFIG_PATH, ++currentConfigLine); 
}

default
{
    state_entry()
    {
		Output("Fresh state");
		
        if(llGetInventoryType(CONFIG_PATH) != INVENTORY_NONE)
        {
            if(llGetInventoryKey(CONFIG_PATH) != NULL_KEY)
            {
                Output("Reading config...");
                configQueryId = llGetNotecardLine(CONFIG_PATH, currentConfigLine);
                return;
            }
            else
            {
                Output("Config file has no key (Never saved? Not full-perm?)");   
            }
        }
        
        state StartServer;
    }
    
    on_rez(integer start_param)
    {
        llResetScript();    
    }

    dataserver(key queryId, string data)
    {
        if(queryId == configQueryId)
        {
            processConfigLine(data);
            
            if(data == EOF)
            {
                state StartServer;
            }
        }
    }
    
    changed(integer change)
    {
        if(change & (CHANGED_OWNER | CHANGED_REGION | CHANGED_REGION_START))
        {
            Output("Resetting...");
            llResetScript();
        }
    }
}

state StartServer
{
    state_entry()
    {
        Output("Server starting...");
        urlRequestId = llRequestURL();
    }
    
    http_request(key requestId, string method, string body)
    {
        if(requestId != urlRequestId)
        {
            return;
        }
        
        if(method == URL_REQUEST_GRANTED)
        {
            assignedUrl = body;
            
            Output("Got URL: " + assignedUrl);
            
            if(authToken == "")
            {
                Output("Registering server...");
                registerRequestId = llHTTPRequest(URL_REGISTER, [HTTP_METHOD, "POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"], "address=" +  llEscapeURL(assignedUrl));
            }
            else
            {
                Output("Updating server...");  
                registerRequestId = llHTTPRequest(URL_UPDATE, [HTTP_METHOD, "POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"], "address=" +  llEscapeURL(assignedUrl) + "&authToken=" + authToken); 
            }
        }
        else if(method == URL_REQUEST_DENIED)
        {
            Output("Failed to acquire URL!");
        }
    }
    
    http_response(key requestId, integer status, list metadata, string body)
    {
        if (requestId == registerRequestId)
        {
            if(status == 200 && llGetSubString(body, 0, 2) == "OK.")
            {
                Output("Registered!");
                Output(llGetSubString(body, 4, -1));
                state ServerRunning;
            }
            else
            {
                Output("Failed to register: " + body);
                return;
            }
        } 
        else
        {
            Output("Unknown request");
        }
    }
    
    changed(integer change)
    {
        if(change & (CHANGED_OWNER | CHANGED_REGION | CHANGED_REGION_START))
        {
            Output("Resetting...");
            llResetScript();
        }
    }
}

state ServerRunning
{
    state_entry()
    {
        Output("Server running...");
    }
	
    http_request(key requestId, string method, string body)
    {
        string requestedPathRaw = ExtractValueFromQuery(llGetHTTPHeader(requestId, "x-query-string"), "path");
        list requestedPathParts = llParseString2List(requestedPathRaw, ["/"], []);
        integer numRequestedPathParts = llGetListLength(requestedPathParts);

        if(numRequestedPathParts == 0)
        {
            llHTTPResponse(requestId, 400, "Bad Request");
            return;
        }
        
		if(!ProcessRequest(requestedPathParts))
		{
			llHTTPResponse(requestId, 501, "Not Implemented");
		}
    }
    
    changed(integer change)
    {
        if(change & (CHANGED_OWNER | CHANGED_REGION | CHANGED_REGION_START))
        {
            Output("Resetting...");
            llResetScript();
        }
    }
}