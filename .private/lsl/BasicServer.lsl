// Server data
string URL_REGISTER = "http://localhost:19420/simstats/action/registerServer.php";
string URL_UPDATE = "http://localhost:19420/simstats/action/updateServer.php";
string URL_CONFIRM = "http://localhost:19420/simstats/action/confirmServer.php";
string serverType = "Population Server";
key registerRequestId;
key updateRequestId;
key urlRequestId; 
key listenKey;
string assignedUrl = "";

string CONFIG_PATH = "Config";
integer currentConfigLine = 0;
key configQueryId = NULL_KEY;
string authToken = "";
key confirmRequestId = NULL_KEY;

///////////////////////////////////////////////////////////////////////////////////////////////////////////
// ++++++  HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK
//                               FOR LOCAL OpenSim TESTING ONLY
///////////////////////////////////////////////////////////////////////////////////////////////////////////
string JSON_OBJECT = "﷑";
string llList2Json( string type, list values )
{
    string buff = "{";
    integer numItems = llGetListLength(values);
    integer i;
    
    for(i = 0; i < numItems; i += 2)
    {
        string itemKey = llList2String(values, i);
        string itemValue = llList2String(values, i+1);
        
        buff += "\"" + itemKey + "\":\"" + itemValue + "\"";
                    
        if(i < numItems-2)
        {
            buff += ",";
        }
    }
    buff += "}";
    
    return buff;
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////
// ----- HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK HACK
///////////////////////////////////////////////////////////////////////////////////////////////////////////

string BuildQueryResult()
{
    list agentsInRegion = llGetAgentList(AGENT_LIST_REGION, []);
    integer numAgentsInRegion = llGetListLength(agentsInRegion);
    
    string response = "{\"Players\":[";
    integer i;
    
    for(i = 0; i < numAgentsInRegion; i++)
    {
        key agentKey = llList2Key(agentsInRegion, i);
        list agentDetails = llGetObjectDetails(agentKey, [OBJECT_SCRIPT_MEMORY, OBJECT_SCRIPT_TIME, OBJECT_POS]);
        
        response += llList2Json(JSON_OBJECT, [
            "DisplayName", llGetDisplayName(agentKey),
            "Username", llGetUsername(agentKey),
            "Key", (string)agentKey,
            "Pos", llList2String(agentDetails, 2),
            "Memory", llList2String(agentDetails, 0),
            "CPU", llList2String(agentDetails, 1)
        ]);
        if(i < numAgentsInRegion-1)
        {
            response += ",";   
        }
    }
    
    response += "]}";
    
    return response;
}

integer ProcessRequest(list pathParts, key requestId)
{
    string firstPathPart = llList2String(pathParts, 0);

    if(firstPathPart == "Base")
    {
        string secondPathPart = llList2String(pathParts, 1);
        
        if(secondPathPart == "GetOnlineUsers")
        {
            llHTTPResponse(requestId, 200, BuildQueryResult());
            return TRUE;
        }
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
        llSetColor(<1, 0, 0>, ALL_SIDES);
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
                updateRequestId = llHTTPRequest(URL_UPDATE, [HTTP_METHOD, "POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"], "address=" +  llEscapeURL(assignedUrl) + "&authToken=" + authToken); 
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
                authToken = llGetSubString(body, 3, -1);
                Output("Your auth token is: " + authToken);
                state ServerUnregistered;
            }
            else
            {
                Output("Failed to register: " + body);
                return;
            }
        } 
        else if(requestId == updateRequestId)
        {
            if(status == 200 && llGetSubString(body, 0, 2) == "OK.")
            {
                Output("Updated!");
                state ServerRunning;
            }
            else
            {
                Output("Failed to update: " + body);
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



state ServerUnregistered
{
    state_entry()
    {
        llSetColor(<1, 1, 0>, ALL_SIDES);
        Output("Click to begin registration...");   
    }
    
    touch(integer num_detected)
    {
        confirmRequestId = llHTTPRequest(URL_CONFIRM, [HTTP_METHOD, "POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"], "serverType=" + llEscapeURL(serverType) + "&authToken=" + authToken);
    }
    
    http_response(key requestId, integer status, list metadata, string body)
    {
        if (requestId == confirmRequestId)
        {
            if(status == 200 && llGetSubString(body, 0, 2) == "OK.")
            {
                Output("Server confirmed!");
                state ServerRunning;
            }
            else
            {
                Output("Failed to confirm server: " + body);
                return;
            }
        } 
        else
        {
            Output("Unknown request");
        }
    }
}

state ServerRunning
{
    state_entry()
    {
        llSetColor(<0, 1, 0>, ALL_SIDES);
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
        
        if(!ProcessRequest(requestedPathParts, requestId))
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