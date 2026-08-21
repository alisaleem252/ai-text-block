# RapidTextAI — User Documentation

Welcome to RapidTextAI! This guide walks you through setting up the plugin and using every feature and field, step by step.

The plugin has three main areas:

1. **Auto Blogging (with the AI Campaign Assistant)** — plan, schedule, and automate content campaigns.
2. **AI Article Generator** — write a single article inside the post editor.
3. **AI Chatbots** — build AI chat widgets for your site.

---

## 0. Getting started (authentication)

1. Install and activate the plugin (see `readme.txt` for installation).
2. In the WordPress admin menu, click **RapidTextAI → RapidTextAI Settings**.
3. Click **Authenticate with RapidTextAI**.
4. A popup opens. Sign in to (or create) your RapidTextAI account.
5. The popup returns your API key automatically and saves it. A toast confirms **"Connected to RapidTextAI"**.

The **Account Status (Monthly)** card now shows your plan, request usage, limit, and remaining requests. Re-authenticate anytime to switch accounts; the status refreshes automatically.

> **Free plan:** 10 AI-generated articles per month. Paid plans start at $10/month.

---

## 1. Auto Blogging with the AI Campaign Assistant

Auto Blogging lets you define **campaigns** — sets of topics with a schedule, model, tone, and publishing rules — that generate and publish posts automatically.

Open **RapidTextAI → Auto Blogging**.

### 1.1 Campaigns list

| Column | Description |
| --- | --- |
| **Campaign Name** | The name you gave the campaign. |
| **Status** | **Enabled** (green) or **Disabled** (gray). Enabled campaigns show the next scheduled run. |
| **Schedule** | Publishing frequency (hourly, twice daily, daily, weekly). |
| **Model** | The AI model used for drafting. |
| **Topics** | Number of topics in the campaign. |
| **Created** | Date the campaign was created. |
| **Actions** | ✎ Edit · ▶/⏸ Enable/Disable · 🗑 Delete. |

Click **+ Add New Campaign** to create one, or **✎** to edit an existing campaign.

### 1.2 Campaign editor — every field

**Campaign Name** *(required)*
A descriptive label, e.g. "Tech Blog Campaign".

**Enable Auto Blogging** *(toggle)*
When ON, the campaign generates posts on its schedule. When OFF, it is saved but paused.

**Publishing Schedule**
- **Every Hour**
- **Twice Daily**
- **Daily**
- **Weekly**

**Post Status**
- **Published** — posts go live immediately.
- **Draft** — posts are saved as drafts for review.
- **Pending Review** — posts wait for a reviewer to approve.

**Post Author**
Choose which user is credited as the post author.

**AI Model**
- Gemini 2.0 (Google)
- Gemini 2.5 (Google)
- DeepSeek 3.1 (DeepSeek)
- DeepSeek 4.0 Pro (DeepSeek)
- Claude 3.7 (Anthropic)
- GPT-5 (OpenAI)
- GPT-4 (OpenAI)
- GPT-3.5 Turbo (OpenAI)
- Grok 3 (xAI)
- GLM-4.6v Flash (Zhipu AI)

**Tone of Voice**
- Informative · Conversational · Formal · Friendly · Persuasive

**Content Topics & Prompts** *(textarea)*
Enter one topic per line. Each line can use the advanced format:

```
Topic: [topic]; Keywords: [keywords]; Tone: [tone]; Audience: [audience]; CTA: [call to action]; Length: [length] : Language: [language]
```

Example:

```
Topic: Complete Guide to Sustainable Gardening; Keywords: sustainable gardening, organic fertilizer, water conservation; Tone: friendly and informative; Audience: beginners; Length: 2500-3000 words; CTA: Download our checklist; Language: English
```

**Improve with AI** — sends your current topics to the AI and rewrites them into the optimized advanced format.

**Advanced Settings** *(collapsible)*

| Field | Description |
| --- | --- |
| **Post Categories** | Check the categories the generated posts are assigned to. |
| **Auto-generate Tags** | ON = the AI creates post tags automatically. |
| **Number of Tags** | 1–10. |
| **Excerpt Length (words)** | 10–100 words for the auto-generated excerpt. |
| **Taxonomy Limit** | 1–10. |
| **Include Images** | ON = insert relevant images into the post body. |
| **Set Featured Image** | ON = set a featured image automatically. |
| **Maximum Images** | 1–10 images per post. |
| **Enable Logging** | ON = record activity to the plugin log. |

**Save Campaign** — saves (and schedules, if enabled).

**Generate Post Now** *(available after the campaign is saved)* — triggers one post immediately using the campaign settings.

**Auto Blogging Logs** *(visible when logging is enabled)* — **Load Logs** / **Clear Logs** to inspect recent pipeline activity.

### 1.3 Setting up a campaign manually (no AI assistant)

You don't need the AI Campaign Assistant to run auto blogging. Follow these steps to create a campaign by hand:

1. Open **RapidTextAI → Auto Blogging**.
2. Click **+ Add New Campaign**.
3. Enter a **Campaign Name** (required).
4. Turn **Enable Auto Blogging** ON to activate it (leave OFF to save it paused).
5. Choose a **Publishing Schedule**: Every Hour, Twice Daily, Daily, or Weekly.
6. Choose a **Post Status**: Published, Draft, or Pending Review.
7. Pick a **Post Author** from the dropdown.
8. Pick the **AI Model** to use for drafting.
9. Pick the **Tone of Voice**.
10. In **Content Topics & Prompts**, add one topic per line — either a plain topic ("How to grow tomatoes") or the advanced format:

    ```
    Topic: [topic]; Keywords: [keywords]; Tone: [tone]; Audience: [audience]; CTA: [call to action]; Length: [length] : Language: [language]
    ```

11. *(Optional)* Click **Improve with AI** to have each topic rewritten into the optimized advanced format.
12. Open **Advanced Settings** and configure:
    - **Post Categories** to assign the posts to
    - **Auto-generate Tags** and the **Number of Tags**
    - **Excerpt Length** and **Taxonomy Limit**
    - **Include Images**, **Set Featured Image**, and **Maximum Images**
    - **Enable Logging** if you want to track runs
13. Click **Save Campaign**. The campaign is saved and, if enabled, scheduled on background cron.
14. *(Optional)* Click **Generate Post Now** to test the campaign and produce one post immediately.
15. If logging is enabled, use **Load Logs** to verify each run completes without errors.

> **Tip:** you can still use the **AI Campaign Assistant** later — it reads your manually created campaigns and can suggest updates or new topics based on them.

### 1.4 How auto blogging generates a post

Enabled campaigns run on a background cron. Each run follows a multi-step pipeline:

1. **Draft** — streams an article from the campaign topic using the selected model.
2. **Polish** — removes placeholders and makes the draft publication-ready.
3. **Heading optimization** — builds image-search queries for each heading.
4. **Title** — generates an SEO title.
5. **Create post** — inserts the post (draft) with excerpt and meta.
6. **Finalize** — adds tags, content images, featured image, and applies the final status.

### 1.5 The AI Campaign Assistant

A chat assistant in a **right-side dock** on the Auto Blogging page. It reads your site's content, SEO settings, categories, tags, and existing campaigns, then helps you plan a better campaign.

**Open** the dock with the **Assistant** button on the right edge of the screen.

**Quick actions** (one-tap buttons):

| Button | What it does |
| --- | --- |
| ✨ **Suggest topics** | Asks the assistant to propose 3–5 topics with keywords and a reason for each. |
| 📋 **Existing campaigns** | Lists your current campaigns. Click **Update →** on one to target it for an update. |
| ➕ **Create new** | Tells the assistant you want a brand-new campaign. |
| ♻️ **Update existing** | Tells the assistant you want to update an existing campaign. |

**Conversation flow:**

1. Ask for topics (or tap **Suggest topics**).
2. The assistant proposes topics and asks whether to **create a new campaign** or **update an existing one**.
3. Answer (or use the quick-action buttons). You can tweak topics, audience, tone, cadence, or keywords in plain language.
4. When confirmed, the assistant outputs a structured campaign.
5. A card appears — **Apply as New Campaign** (green) or **Update Campaign** (blue, showing the target campaign name).
6. Click it. The campaign list refreshes immediately.

---

## 2. AI Article Generator

The article generator writes a single, complete article and loads it into the WordPress editor.

Open any **post or page** in the editor, then click **Generate Article** (next to the Featured Image meta box).

### 2.1 Fields

**Write an article about** *(required)*
The main topic, e.g. "How to grow tomatoes in containers".

**Focus Keywords** *(required)*
Comma-separated keywords, e.g. "tomatoes, container gardening".

**Tags & Categories** *(slider, 0–10)*
Number of tags to generate and apply.

**Content Images** *(slider, 0–6)*
Number of relevant images to insert into the article.

**Generate Excerpt** *(toggle)*
ON = generate and set the post excerpt.

**Set Featured Image** *(toggle)*
ON = find, upload, and set a featured image.

**Select Model**
- Gemini 2.0 (Google) · Gemini 2.5 (Google)
- Deepseek 3.1 (DeepSeek) · Deepseek v4 (DeepSeek)
- Claude 3.7 Sonnet (Anthropic)
- GLM-4.6V Flash (Zhipu AI)
- GPT-5 (OpenAI) · GPT-4 (OpenAI) · GPT-3.5 (OpenAI)
- Grok 2 (xAI) · Grok 3 (xAI)

### 2.2 Advanced Options

Click **Show Advanced Options** to reveal:

| Field | Options / notes |
| --- | --- |
| **Preferred Article Length** | 100–200, 300–500, 500–700, 1000–1500, 2000–3000+ words |
| **Target Audience** | Free text, e.g. "students, professionals" |
| **Tone** | Formal · Conversational · Persuasive · Friendly · Neutral |
| **Writing Style** | Informative · Narrative · Technical · Descriptive · Explanatory |
| **References/Sources** | Free text — URLs or sources to include |
| **Article Structure** | Introduction/Body/Conclusion · Problem/Solution · Cause/Effect · Listicle · How-to |
| **Internal Links** | Comma-separated URLs to your own pages |
| **External Links** | Comma-separated URLs to external sites |
| **Include a Call-to-Action** | e.g. "Subscribe now", "Learn more" |

### 2.3 Generation Mode

- **Writing Mode** — fast, single-pass generation with your selected model (1 request).
- **Agent Mode** — a 4-step pipeline (Draft → Polish → Heading Optimization → Final Assembly) that produces more polished, publication-ready content (~4 requests). The card shows your remaining requests before you start.

Click **Generate Article** (or **Run Agent Mode**). A modal streams live progress. When finished, the article — with images, title, tags, excerpt, and featured image (if enabled) — is inserted into the editor.

---

## 3. AI Chatbots

Create and manage AI chat widgets for the front end of your site.

Open **RapidTextAI → AI Chatbots**.

### 3.1 Chatbot list

| Column | Description |
| --- | --- |
| **Name** | Chatbot name. |
| **Shortcode** | `[rapidtextai_chatbot id="…"]` — paste this anywhere to embed it. |
| **Model** | The AI model. |
| **Theme** | Visual theme. |
| **Status** | Active or Inactive. |
| **Actions** | ✎ Edit · 🗑 Delete. |

Click **+ Add New Chatbot**, or **✎** to edit.

### 3.2 Chatbot editor — tabs and fields

**General tab**

| Field | Notes |
| --- | --- |
| **Chatbot Name** *(required)* | Label for the chatbot. |
| **Description** | Internal note (optional). |
| **AI Model** | GPT-3.5 Turbo, GPT-4, GPT-4 Turbo. Click **Refresh Models** to load more models from your account. |
| **Status** | Active / Inactive. |
| **System Message** | Instructions that define how the chatbot behaves. |
| **Welcome Message** | The first message users see. |

**Appearance tab**

| Field | Options |
| --- | --- |
| **Theme** | Modern · Classic · Minimal · Dark |
| **Position** | Bottom Right · Bottom Left · Top Right · Top Left |
| **Size** | Small · Medium · Large |
| **Primary Color** | Color picker + hex |
| **Text Color** | Color picker + hex |
| **Background Color** | Color picker + hex |
| **Show Avatar** | Toggle |
| **Avatar URL** | Image URL for the avatar |

**Behavior tab**

| Field | Notes |
| --- | --- |
| **Automatically open chatbot** | Toggle |
| **Delay (milliseconds)** | e.g. 3000 = open after 3 s |
| **Max Tokens** | Max response length (1–4000) |
| **Temperature** | 0 = focused, 1 = balanced, 2 = creative |

**Knowledge Base tab**
Add documents (title + content) the chatbot uses for context. **+ Add Document** / **Remove**.

**Tools tab**
Connect the chatbot to external APIs. Each tool has:

| Field | Notes |
| --- | --- |
| **Tool Name** | Function name the AI can call. |
| **HTTP Method** | GET · POST · PUT · PATCH · DELETE |
| **Description** | Tells the AI when/how to use the tool. |
| **API URL** | Endpoint, e.g. `https://api.example.com/endpoint` |
| **Headers (JSON)** | `[{"key":"Authorization","value":"Bearer token"}]` |
| **Response Field** | Optional JSON path to extract, e.g. `data.result` |
| **Parameters (JSON)** | JSON Schema describing the tool's arguments |

Click **Create Chatbot** / **Update Chatbot** to save. The shortcode appears after saving — copy it into any post, page, or widget to embed the chat widget.
