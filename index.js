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
    const formData = req.body;
    const result = await formCollection.insertOne(formData);
    res.json({ success: true, insertedId: result.insertedId });
  } catch (error) {
    console.error(error);
    res.status(500).json({ error: "Failed to save entry" });
  }
});


app.listen(PORT, () => {
  console.log(`🚀 Server listening on port ${PORT}`);
});
