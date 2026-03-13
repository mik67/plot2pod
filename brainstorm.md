Hi, Claude.

07:27
Hey, Miloš! How's it

Hey, Milo. Okay. I am fine. Thank you. I will just continue my previous conversation regards to the business to the growth to both site.

07:28
Sounds good! I'm ready to help with your business growth plans. What would you like to focus on right now - are we talking about strategy, specific improvements, or something else?

I would like to have a brainstorming regards my my bot domain plot to bot. Like like, I would like to have it as a server which allows people to to provide their stories, to upload their stories, their ideas, and the site will convert it to audio and to publish it and make it available for for the other people. Let's... so that was one part. and then I realized that it it will be nice to have it as a as a studying format that people could provide their their own materials and ask to create podcast or provide some sources based on which it should create the podcast or just provide the ideas... request ideas of what they would like to know, what they do... what they would like to teach about by creating by by the, uh, podcast which the site will create for them and for other people which will be interested.

07:29
Okay, so you're looking at two connected ideas here. First, a platform where people share stories and ideas, and your system converts them to audio and publishes them. Then second, a learning angle where people can provide study materials or sources, and you generate podcasts from that content for them and others to consume. That's really interesting because it positions you as both a publishing platform and an educational tool. Are you thinking these would be separate features on the same site, or more like two different product angles you're exploring?

It could be on the same side, like, different different part of of the... of this side. Like, when it'll be... let's just say, my phone stories or or just stories, like, literature. And the other part will be education. Could be one one side.

07:30
Right, so one unified platform with two main sections - a stories and literature hub, and an education hub. Both powered by your text-to-audio conversion. That makes sense from a user perspective too, since people might want to use both features. What's your biggest challenge right now - is it the technical side of building this, figuring out the business model, or something else entirely?

I'm thinking to... for the podcast. Okay. The podcast part, I could reuse... for start, I could start with notebook alignment, which is ideal. There is the option to create podcast with woman and man voice, like, they are debating... debating about topic and explaining to the the stuff. So this will be for the podcast. And for for the stories and the books. It could be... I... I'm not sure how to use it yet, but it it would be a voice server application. Just the downside is that they do not provide API. It's manually based so far, so it's it's it's... it would require quite a manual input. What do you what do you think? What do you advise?

07:31
Got it. So you're thinking NotebookLM for the podcast side - that's smart since it already does the debate format with multiple voices, which is perfect for educational content. For the stories and books side, you're looking at a voice service but it's manual, which would slow things down.

Honestly, I'd focus on launching the podcast education feature first since NotebookLM has that API potential and scales better. For stories, you might want to explore other text-to-speech APIs like Google Cloud TTS, ElevenLabs, or PlayHT that have proper APIs built in - they'd let you automate the whole process instead of manual work. What's your timeline looking like for getting this live?

What are you doing there? Doesn't matter. I would like to make it as soon as possible, but I am I am still working as well. So it's sort of side project. So, yeah, I... I'd like to progress as quickly as possible, but there there is no no milestone or not not any problem today.

07:33
Right, so you want to move fast but you're juggling this alongside your main job. My advice would be to start with just the podcast education feature using NotebookLM - that's your quickest path to launch since the tooling is already there. Get that working, get some users, and then layer in the stories and books feature once you have momentum. Does that feel doable?

Well, it sounds good, actually. I I love this. I am using as well cloak cloak, so I I know how to do the research in case somebody will request the the topic request podcast on on topic only. So... yeah. It's... I think it's good.

07:33
Perfect, so you've got the research workflow down with Claude already. That's a solid foundation - you can handle topic requests, pull together the source materials, feed them into NotebookLM, and generate those debate-style podcasts. Sounds like you're pretty well positioned to get this off the ground. What's your next step - building out the site itself, or mapping out the user flow first?

I don't know. What do you recommend? I I I was thinking to sort of start with both.

07:34
I'd say start with the user flow first, honestly. Figure out how someone actually uses this - they submit a topic, you do research, you create the podcast, they get notified it's ready. Once that's clear in your head, building the site becomes way faster because you know exactly what you need to build. You could sketch that out in like an hour, then jump into the technical side. Does that sound reasonable?

The pizza.

07:35
Sure, let's map out the user flow. So here's what I'm thinking: someone comes to your site,

Yeah. Talk.

07:35
Alright, so let's map out the user flow. Here's what I'm picturing: A user comes to your site and submits a podcast request - they either give you a topic, provide source materials, or describe what they want to learn about. Then you receive that request, do your research using Claude, compile the materials, feed it into NotebookLM to generate the debate-format podcast with male and female voices, and then you upload it back to your site so they and others can listen. Does that capture it, or am I missing something?

Yes.

07:36
Great, so we're on the same page. Now let's break down the actual steps a user would take on the site. What does the homepage look like - do they see existing podcasts first, or do they go straight to submitting a request? And how do they submit - is it a simple form, do they upload files, paste text?

I don't know if I I think they could... that as a landing page, front page, they could see our... the existing forecast and they they needs to sort of log in to be able to to post anything.

07:37
Makes sense. So the homepage showcases your best podcasts to hook people, then they log in to submit their own requests. That's clean. Once they're logged in, what information do you need from them to create a podcast - just the topic, or do you want them to upload files, provide sources, describe the angle they want?

Well, that that depends, uh, if they if they will have their own materials, they'll upload their own materials in case they have got just link to sources. They will provide the link to sources. And in case they they have just the topic and sort of, like, to me to do the research and all around, Just tell tell me the idea.

07:37
Perfect, so you give them three options on the submission form - upload files, paste links to sources, or just give you a topic and you handle the research. That's flexible and covers all the bases. Then on your end, you gather everything, do the research if needed, compile it for NotebookLM, generate the podcast, and notify them when it's ready. Sound right?

Yeah. Let's continue.

09:41
Got it. So the homepage showcases existing podcasts to hook them, then they log in to create. Once logged in, they have three options: upload their own materials, paste source links, or just give you a topic and let you research it. That's really flexible and covers all the ways people might want to use it.

Now, what happens after they submit? Do they get notified when their podcast is ready, and can they edit or request changes before it goes live?

Yes. Let's say yes for both questions.

09:41
Perfect. So they get notified when it's ready, can review it, maybe request tweaks, and then it publishes. That's a solid flow. Now here's the practical question - who decides what goes live? Do you review everything before publishing, or does it auto-publish once they approve it?

I think as a starting point, I can review it because it's gonna be so many of of them. But then as time goes, if if the amount of people grows, then it should be someday automated.

09:42
Smart move. You quality-check everything at the start, keep the standard high, then automate later when you've got volume. That also gives you time to figure out what makes a good podcast on your platform. So your workflow is: request comes in, you do the research and create it, they review and approve, you do a final check, then publish. Sound right?

Yeah. Sounds right.

09:42
Excellent. So now you've got your user flow locked down. Next thing - what's your tech stack looking like? Do you already have ideas for how you'll build the site itself, or is that something you want to figure out next?

On the side, I have just a rough ideas. Not sure how exactly it will it will... it's... I would like it looks so we can do some brainstorm regards to this.

09:43
Sure, let's brainstorm the tech side. What's your background - are you coding this yourself, or will you need to hire someone? And do you have any preferences, like do you want something quick and simple to launch, or are you thinking long-term scalability from day one?

I am IT professional. Not not, um, oriented for web, but, uh, I have some experience with, uh, with, uh, web design and the HTML, JavaScript, and PHP, but, uh, I am planning to use AI. Anyway, I am using Claude Claude for for my projects. So I I think I should be fine from the technical point of view.

09:44
Ah, solid. So you've got the fundamentals and you're comfortable using Claude to help build it out. That's actually the smart way to do it - you can move way faster leaning on AI for the scaffolding and problem-solving. Given your stack, are you thinking a traditional backend like PHP with a database, or would you consider something like Node.js or Python to keep everything more modern? What feels natural to you?

The thing is that I do not have too much experiences with, uh, with Python or Node. But, um, I would like to I would like to learn, but I'm not sure how... if time allows me to do. So what would you recommend?

09:45
Honestly? Stick with PHP for now. You know it, you can build fast, and you can always refactor later. Learning Node or Python while building your MVP is just extra friction you don't need right now. Get the product out, get users, then level up your stack if you want. Does that make sense?

Yeah.

09:45
Great

That makes sense.

09:45
Good

. So PHP backend it is. Now, for the database - are you thinking MySQL or PostgreSQL, or do you have a preference? And do you need to handle file uploads for when people submit their own materials?

The database, I would prefer the MySQL. I I just... actually, it's Maria and the DB, but it's a version of MySQL.

09:46
Perfect, MariaDB is solid and you probably already have hosting that supports it. Yeah, so for file uploads - when people submit their own materials or sources, you need somewhere to store those files. Are you thinking server storage, or cloud storage like AWS S3 or similar?

my sync. I'm not sure on on this question. I think some cloud storage should be nice.

09:46
Cloud storage makes sense - it scales better and you don't have to manage server space yourself. AWS S3 is the standard, but there's also Google Cloud Storage or even simpler options like Backblaze B2 if you want cheaper. For now, just pick one and move forward - you can always switch later. Sound good?

Yeah. Sounds good.

09:47
Great. So you've got PHP backend, MariaDB, cloud storage for files. What about the frontend - are you building custom HTML and JavaScript, or would you consider a framework like Vue or React to speed things up?

The transfer framework or plugin at a at a closed call to there is the front end design plugin, which is quite nice.

09:47
I'm not totally following - are you talking about a specific WordPress plugin or framework you've used before? Can you spell out the name or describe what it does?

I'm talking about plug in for Claude. The plug in is called front front end design. It's quite nice.

09:47
Ah got it - so there's a Claude plugin called Front End Design that helps you generate UI code. That could definitely speed things up. You could use that to generate your HTML and JavaScript, then customize as needed. That works well with your approach of leaning on AI to move faster. Are you thinking of using that for the whole interface, or just parts of it?

The whole interface.

09:48
Perfect. So Claude's Front End Design plugin handles your UI, you manage the PHP backend and MariaDB, cloud storage for files, and NotebookLM handles the podcast generation. You're actually looking pretty set up to start building. What's holding you back from jumping in right now?

I I have meeting right now, so I can jump jump on this a little bit later.

09:48
No problem! You've got a solid plan now - user flow is mapped, tech stack is locked in, you know your next steps. Jump back to it when you've got time. Good luck with the meeting, Miloš.

Thank you, Claude. Thanks a lot.

09:49
You're welcome! Feel free to reach out whenever you want to brainstorm more or hit a snag. Talk soon!