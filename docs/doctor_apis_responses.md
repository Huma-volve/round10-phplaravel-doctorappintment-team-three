## Doctor APIs Responses

### GET /api/doctors/{id}

**Envelope**

```json
{
  "success": true,
  "message": null,
  "data": {
    "id": 1,
    "name": "Dr. Alice Smith",
    "email": "doctor0@example.com",
    "specialization": "General Practitioner",
    "clinic": {
      "id": 1,
      "name": "Central Health Clinic",
      "phone": "+1-555-1000",
      "address": "123 Main St",
      "latitude": 37.7749,
      "longitude": -122.4194
    },
    "clinic_address": "123 Main St",
    "license_number": "LIC-1",
    "bio": "Experienced doctor for testing purposes.",
    "session_price": 100,
    "rating": 4.5,
    "reviews_count": 10,
    "is_favorite": true,
    "reviews": [
      {
        "id": 1,
        "patient_name": "Test Patient",
        "rating": 5,
        "comment": "Great doctor!",
        "created_at": "2026-03-17T00:00:00.000000Z"
      }
    ]
  }
}
```

### GET /api/doctors/nearby

**Envelope**

```json
{
  "success": true,
  "message": null,
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Dr. Alice Smith",
        "specialization": "General Practitioner",
        "clinic_name": "Central Health Clinic",
        "session_price": 100,
        "rating": 4.5,
        "reviews_count": 10,
        "is_favorite": true,
        "distance_km": 1.23
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 10,
      "total": 2
    }
  }
}
```

When `lat` or `lng` is `null`, the `distance_km` field is omitted and doctors are ordered by `rating` then `reviews_count`.

### POST /api/doctors/{id}/favorite

```json
{
  "success": true,
  "message": null,
  "data": {
    "is_favorite": true
  }
}
```

### DELETE /api/doctors/{id}/favorite

```json
{
  "success": true,
  "message": null,
  "data": {
    "is_favorite": false
  }
}
```

All responses are wrapped by the JSON response helper: `success`, `message`, and `data` (or `errors` on failures).

