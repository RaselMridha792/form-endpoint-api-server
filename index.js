require("dotenv").config();
const express = require('express');
const { MongoClient, ServerApiVersion, ObjectId } = require("mongodb");
const app = express();
const cors = require("cors");

const PORT = process.env.PORT || 3000;
app.use(cors());
app.use(express.json());


const uri = "mongodb+srv://form_api_rasel:uFNsgTMyU9fu0xS4@cluster0.epzmh.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0";

const client = new MongoClient(uri, {
    serverApi: {
        version: ServerApiVersion.v1,
        strict: true,
        deprecationErrors: true,
    }
});

let formCollection;

async function run() {
    try {
        await client.connect();
        console.log("✅ Connected to MongoDB!");

        const database = client.db("form_api");
        formCollection = database.collection("form_entries");

        console.log("✅ Database and Collection ready!");

    } catch (error) {
        console.error(" MongoDB Connection Error:", error);
    }
}
run().catch(console.dir);


app.use((req, res, next) => {
    console.log('🔥 [LOG] NEW REQUEST: ', req.method, req.url);
    console.log('🔥 [LOG] BODY:', req.body);
    next();
});

app.get("/entries", async (req, res) => {
    try {
        const entries = await formCollection.find().toArray();
        res.json(entries);
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: "Failed to fetch entries" });
    }
});


app.get('/', (req, res) => {
    res.send('✅ Welcome to Form API Server! Available endpoints: /entries (GET, POST)');
});
app.post("/entries", async (req, res) => {
  try {
    console.log("✅ [POST /entries] Incoming data:", req.body);

    if (!formCollection) {
      console.error("❌ formCollection is not initialized!");
      return res.status(500).json({ error: "DB not ready" });
    }

    const formData = req.body;

    // Validation
    if (!formData || Object.keys(formData).length === 0) {
      console.error("❌ Empty body received!");
      return res.status(400).json({ error: "Empty data" });
    }

    // Save to MongoDB
    const result = await formCollection.insertOne(formData);
    console.log("✅ Data saved to MongoDB:", result.insertedId);

    res.json({ success: true, insertedId: result.insertedId });
  } catch (error) {
    console.error("❌ Error saving entry:", error);
    res.status(500).json({ error: "Failed to save entry" });
  }
});


app.listen(PORT, () => {
    console.log(`🚀 Server listening on port ${PORT}`);
});
