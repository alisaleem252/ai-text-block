# AI Chatbot Tools

This guide explains how to configure and use Tools and PHP Functions in your AI Chatbot.

## Tools (External APIs)

Tools allow your chatbot to call external APIs and services. Each tool is defined with a name, description, API endpoint configuration, and JSON parameters schema.

### Tool Configuration

- **Tool Name**: Unique identifier for the tool (e.g., `get_weather`, `send_email`)
- **Description**: What the tool does (helps the AI decide when to use it)
- **API URL**: The endpoint URL for the external service
- **HTTP Method**: GET, POST, PUT, PATCH, or DELETE
- **Headers**: JSON array of HTTP headers for authentication/configuration
- **Response Field**: Optional field to extract from JSON response
- **Parameters (JSON)**: JSON schema defining the expected parameters

### Tool Examples

#### Weather API Tool
**API URL**: `https://api.openweathermap.org/data/2.5/weather`
**Method**: `GET`
**Headers**:
```json
[
    {"key": "Content-Type", "value": "application/json"}
]
```
**Parameters**:
```json
{
    "type": "object",
    "properties": {
        "q": {
            "type": "string",
            "description": "City name, state code and country code divided by comma"
        },
        "appid": {
            "type": "string",
            "description": "Your API key"
        },
        "units": {
            "type": "string",
            "enum": ["standard", "metric", "imperial"],
            "description": "Temperature units"
        }
    },
    "required": ["q", "appid"]
}
```
**Response Field**: `weather.0.description`

#### SendGrid Email Tool
**API URL**: `https://api.sendgrid.com/v3/mail/send`
**Method**: `POST`
**Headers**:
```json
[
    {"key": "Authorization", "value": "Bearer YOUR_SENDGRID_API_KEY"},
    {"key": "Content-Type", "value": "application/json"}
]
```
**Parameters**:
```json
{
    "type": "object",
    "properties": {
        "personalizations": {
            "type": "array",
            "items": {
                "type": "object",
                "properties": {
                    "to": {
                        "type": "array",
                        "items": {
                            "type": "object",
                            "properties": {
                                "email": {"type": "string"}
                            }
                        }
                    }
                }
            }
        },
        "from": {
            "type": "object",
            "properties": {
                "email": {"type": "string"}
            }
        },
        "subject": {
            "type": "string",
            "description": "Email subject"
        },
        "content": {
            "type": "array",
            "items": {
                "type": "object",
                "properties": {
                    "type": {"type": "string"},
                    "value": {"type": "string"}
                }
            }
        }
    },
    "required": ["personalizations", "from", "subject", "content"]
}
```

#### Zapier Webhook Tool
**API URL**: `https://hooks.zapier.com/hooks/catch/YOUR_WEBHOOK_ID/`
**Method**: `POST`
**Headers**:
```json
[
    {"key": "Content-Type", "value": "application/json"}
]
```
**Parameters**:
```json
{
    "type": "object",
    "properties": {
        "name": {
            "type": "string",
            "description": "Contact name"
        },
        "email": {
            "type": "string",
            "description": "Contact email"
        },
        "message": {
            "type": "string",
            "description": "Contact message"
        },
        "source": {
            "type": "string",
            "description": "Source of the contact (e.g., chatbot)"
        }
    },
    "required": ["name", "email", "message"]
}
```

#### Slack Notification Tool
**API URL**: `https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK`
**Method**: `POST`
**Headers**:
```json
[
    {"key": "Content-Type", "value": "application/json"}
]
```
**Parameters**:
```json
{
    "type": "object",
    "properties": {
        "text": {
            "type": "string",
            "description": "Message text"
        },
        "channel": {
            "type": "string",
            "description": "Slack channel (optional)"
        },
        "username": {
            "type": "string",
            "description": "Bot username (optional)"
        }
    },
    "required": ["text"]
}
```

This will help you debug and ensure your functions work correctly before users interact with them through the chatbot.

## External Tool Configuration Tips

1. **Authentication**: Most APIs require authentication. Common methods:
   - API Key in headers: `{"key": "Authorization", "value": "Bearer YOUR_API_KEY"}`
   - API Key in URL parameters: Add to the API URL as query parameter

2. **Response Handling**: Use the "Response Field" to extract specific data from JSON responses. For example:
   - `data.result` extracts the `result` field from `{"data": {"result": "value"}}`
   - `weather.0.description` extracts description from the first weather item

3. **Error Handling**: External APIs may fail. The system will return error messages to help users understand what went wrong.

4. **Testing**: Use tools like Postman or curl to test your API endpoints before configuring them in the chatbot.