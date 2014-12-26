// Server data
string URL_REGISTER = "http://simstats.nooperation.net/registerQueryServer.php";
key registerRequestId;
key urlRequestId;
key listenKey;
string assignedUrl = "";

// Agent count history data
integer MAX_QUERY_CACHE_AGE = 3;
integer lastAgentCount = -1;
integer lastTime = -1;
string agentCountHistory = ""; // Format: <initial timestamp>(,<timse since previous timestamp>,<agentCount>)+

UpdateDisplay()
{
    integer freeMemory = llGetFreeMemory();
    vector textColor;
    
    if(freeMemory > 16384)
    {
        textColor = <0, 1, 0>;
    }  
    else if(freeMemory > 4096)
    {
        textColor = <1, 0.5, 0>;
    } 
    else
    {
        textColor = <1, 0, 0>;
        llOwnerSay("Warning! Low memory!");
    }
    
    llSetText("Free memory: " + (string)freeMemory, textColor, 1.0);  
}

Output(string message)
{
    llOwnerSay(message);
}

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

default
{
    state_entry()
    {
        Output("Acquiring URL...");
        urlRequestId = llRequestURL();
        llSetTimerEvent(1);
    }
    
    touch_end(integer num_detected)
    {
        if(llDetectedKey(0) != llGetOwner())
        {
            return;   
        }
        
        llOwnerSay("QueryServer agentCountHistory = " + agentCountHistory);
        UpdateDisplay();
    }
    
    on_rez(integer start_param)
    {
        llResetScript();    
    }
    
    timer()
    {
        integer agentCount = llGetRegionAgentCount();
        if(agentCount != lastAgentCount)
        {            
            integer currentTime = llGetUnixTime();
            if(lastTime == -1)
            {
                agentCountHistory += (string)currentTime; 
                lastTime = currentTime;
            }

            agentCountHistory += "," + (string)(currentTime - lastTime) + "," + (string)(agentCount);
            
            lastTime = currentTime;
            lastAgentCount = agentCount;
            
            UpdateDisplay();
        }
    }
    
    http_request(key requestId, string method, string body)
    {
        if(requestId == urlRequestId)
        {
            if(method == URL_REQUEST_GRANTED)
            {
                assignedUrl = body;
                
                Output("Got URL: " + assignedUrl);
                Output("Registering server...");
                registerRequestId = llHTTPRequest(URL_REGISTER + "?queryUrl=" + llEscapeURL(assignedUrl), [], "");
            }
            else if(method == URL_REQUEST_DENIED)
            {
                Output("Failed to acquire URL!");
            }
            
            return;
        }
        
        string requestedPathRaw = llGetHTTPHeader(requestId, "x-path-info");
        list requestedPathParts = llParseString2List(requestedPathRaw, ["/"], []);
        integer numRequestedPathParts = llGetListLength(requestedPathParts);
        
        if(numRequestedPathParts == 0)
        {
            llHTTPResponse(requestId, 400, "Bad Request");
            return;
        }
        
        string firstPathPart = llList2String(requestedPathParts, 0);
        if(firstPathPart == "population")
        {
            llHTTPResponse(requestId, 200, agentCountHistory);
            //lastAgentCount = -1; // Don't reset age so duplicate entries for population aren't reported.
            lastTime = -1;
            agentCountHistory = "";
            return;
        }
        else if(firstPathPart == "agents")
        {
            llHTTPResponse(requestId, 200, BuildQueryResult());
            return;
        }
        
        llHTTPResponse(requestId, 501, "Not Implemented");
    }
    
    http_response(key requestId, integer status, list metadata, string body)
    {
        if (requestId == registerRequestId)
        {
            if(status == 200 && body == "OK")
            {
                Output("Registered!");
                return;
            }
            else
            {
                Output("Failed to register: " + body);
                return;
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
