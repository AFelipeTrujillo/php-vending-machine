Run the 3 README examples against the live Docker container to verify the vending machine works correctly.

Before starting, reset the machine state by calling:
`curl -s -X POST http://localhost:8080/service -H "Content-Type: application/json" -d '{"items":{"water":10,"juice":10,"soda":10},"coins":{"0.05":20,"0.10":20,"0.25":20,"1.00":10}}'`

Then run each example and show the actual responses:

---

**Example 1 — Buy Soda with exact change**
Expected output: `{ "item": "SODA", "change": [] }`

```
POST /coins  {"coin": "1"}
POST /coins  {"coin": "0.25"}
POST /coins  {"coin": "0.25"}
POST /items/soda
```

---

**Example 2 — Insert money then return coin**
Expected output: `{ "returned": [0.10, 0.10] }`

```
POST /coins  {"coin": "0.10"}
POST /coins  {"coin": "0.10"}
POST /return-coin
```

---

**Example 3 — Buy Water without exact change**
Expected output: `{ "item": "WATER", "change": [0.25, 0.10] }`

```
POST /coins  {"coin": "1"}
POST /items/water
```

---

For each example, show the curl commands you ran, the actual JSON response, and whether it matches the expected output (✓ or ✗). End with a summary: how many examples passed out of 3.
