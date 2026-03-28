# Offline/Online Synchronization Documentation
## UZRS MOI Collection System

---

## Overview

The UZRS MOI Collection System implements a robust offline-first architecture with periodic online synchronization. This allows users to work seamlessly on local computers and sync their data to a central remote server when internet connectivity is available.

---

## Architecture

### Database Configuration

**File:** `config/database.php`

The system automatically detects whether it's running locally or on the remote server:

- **Local Environment**: Connects to local MySQL database (localhost, root user)
- **Remote Environment**: Connects to production database (uzrssoft.com)

**Key Features:**
- ✅ Automatic environment detection
- ✅ Smart remote host selection (uses 'localhost' when on remote server for better performance)
- ✅ Connection timeout handling (5 seconds connect, 10 seconds read)
- ✅ UTF-8MB4 charset for Tamil language support
- ✅ Graceful error handling

---

## Sync Mechanism

### Core Concept: UUID-Based Tracking

Each record across all synced tables has three key fields:

1. **`uuid`** (CHAR(36)): Universally unique identifier for the record
   - Generated automatically on record creation
   - Used to match records between local and remote databases
   - Format: e.g., "67891234-a1b2c3d4e5f6g7h8"

2. **`is_synced`** (TINYINT(1)): Sync status flag
   - `0`: Record has local changes that need to be synced
   - `1`: Record is in sync with remote server

3. **`remote_id`** (INT(11)): Remote database ID
   - Stores the ID of the corresponding record on remote server
   - NULL if record hasn't been synced yet
   - Used for efficient updates

### Synced Tables

1. **users** - User accounts
2. **functions** - Event/Function details
3. **collections** - Collection entries
4. **expenses** - Expense records

### Sync Flow

```
┌─────────────────┐
│  Local Action   │
│ (Create/Update) │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Set is_synced  │
│     = 0         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   User Clicks   │
│   "Sync Data"   │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────────┐
│         api/sync_data.php               │
│                                         │
│  1. Check remote connection             │
│  2. Ensure all columns exist            │
│  3. Sync deletions (deleted_records)    │
│  4. Sync users (match by UUID/phone)    │
│  5. Sync functions (match by UUID)      │
│  6. Sync collections (match by UUID)    │
│  7. Sync expenses (match by UUID)       │
│  8. Update is_synced = 1                │
│  9. Store remote_id for future updates  │
└────────┬────────────────────────────────┘
         │
         ▼
┌─────────────────┐
│  Sync Complete  │
│  Show Stats     │
└─────────────────┘
```

---

## How It Works

### Creating a New Record

When a new collection, function, or expense is created:

```php
// Automatically generates UUID and sets is_synced = 0
INSERT INTO collections (..., uuid, is_synced) 
VALUES (..., UUID(), 0)
```

### Updating an Existing Record

When a record is edited:

```php
// Marks record as needing sync
UPDATE collections 
SET ..., is_synced = 0 
WHERE id = ?
```

### Deleting a Record

When a record is deleted:

```php
// 1. Record UUID in deleted_records table
INSERT INTO deleted_records (table_name, uuid) 
VALUES ('collections', ?)

// 2. Delete the actual record
DELETE FROM collections WHERE id = ?
```

During sync, the remote record with matching UUID is also deleted.

### Synchronization Process

**File:** `api/sync_data.php`

**Step-by-Step Process:**

1. **Connection Check**
   - Connects to local database
   - Attempts connection to remote database
   - Returns error if remote is unavailable

2. **Schema Verification**
   - Checks if `uuid`, `is_synced`, `remote_id` columns exist
   - Automatically creates missing columns if needed
   - Creates `deleted_records` table if it doesn't exist

3. **Sync Deletions**
   - Processes all records in `deleted_records` table
   - Deletes corresponding records from remote database using UUID
   - Removes processed items from `deleted_records`

4. **Sync Users**
   - Gets all users from local database
   - For each user:
     - Generate UUID if missing
     - Check if remote_id exists → UPDATE remote record
     - If no remote_id, search by UUID → Link existing
     - If not found by UUID, search by phone → Link existing
     - If still not found → INSERT new record
     - Update local record with remote_id and is_synced = 1

5. **Sync Functions**
   - Similar process as users
   - Matches by UUID first
   - Falls back to matching by user_id + function_name + date + place
   - Maps local user_id to remote user_id using userMap

6. **Sync Collections**
   - Similar process but stricter
   - Only matches by UUID (no fallback)
   - Maps local function_id and user_id to remote counterparts
   - Includes all denomination details

7. **Sync Expenses**
   - Similar to collections
   - Maps related_collection_id if applicable

8. **Return Statistics**
   ```json
   {
     "success": true,
     "message": "தரவு ஒத்திசைவு வெற்றிகரமாக முடிந்தது!",
     "stats": {
       "users": 5,
       "functions": 3,
       "collections": 127,
       "expenses": 8,
       "deleted": 2
     }
   }
   ```

---

## User Interface

### Sync Button

**Location:** Every main page (dashboard, collection_entry, etc.)

**File:** `js/sync.js`

```javascript
// Triggered when user clicks sync button
fetch('api/sync_data.php')
  .then(response => response.json())
  .then(data => {
    // Shows success message with statistics
    // Or error message if sync failed
  })
```

### Sync Management

**File:** `api/undo_sync.php`

Provides two options:

1. **Reset Local Sync Status**
   - Marks all local records as `is_synced = 0`
   - Forces re-check during next sync
   - Useful if you want to verify all data

2. **Delete Remote Data (Full Undo)**
   - Deletes all synced records from remote server
   - Resets local records to `is_synced = 0`, `remote_id = NULL`
   - Use only if sync was done by mistake

---

## Testing & Diagnostics

### Test Sync Tool

**File:** `api/test_sync.php`

Comprehensive diagnostic tool that checks:

1. **Database Connections**
   - Local database status
   - Remote database status

2. **Table Structure**
   - Verifies all required columns exist
   - Shows which tables are ready for sync

3. **Sync Statistics**
   - Total records per table
   - Synced vs pending records
   - Sync completion percentage

4. **UUID Coverage**
   - Shows how many records have UUIDs
   - Identifies records that need UUIDs

5. **Recent Activity**
   - Lists recently synced records
   - Shows local ID to remote ID mapping

6. **Recommendations**
   - Alerts for high pending count
   - Warnings for missing UUIDs
   - Connection issues

**Access:** `http://your-domain/api/test_sync.php`

---

## Best Practices

### When to Sync

1. **End of Day**: Sync all daily collections before closing
2. **Before Important Reports**: Ensure data is synced before generating reports
3. **After Bulk Changes**: Sync after major data entry sessions
4. **Regular Schedule**: Set up a sync routine (e.g., every 2-3 hours)

### Handling Sync Errors

**No Internet Connection:**
- Error: "இணையதள சேவையகத்துடன் இணைக்க முடியவில்லை"
- Solution: Check internet connection, try again later

**Sync Timeout:**
- Cause: Large amount of data or slow connection
- Solution: Sync may have partially completed, check test_sync.php for status

**Duplicate Records:**
- Prevention: System uses UUIDs to prevent duplicates
- If occurs: Contact administrator, may indicate UUID collision (extremely rare)

---

## Troubleshooting

### Problem: Sync button doesn't respond

**Check:**
1. Open browser console (F12) for JavaScript errors
2. Verify `js/sync.js` is loaded
3. Check if user is logged in

### Problem: Sync fails with "Unauthorized"

**Solution:**
- User session may have expired
- Log out and log back in

### Problem: Records showing as "pending sync" even after sync

**Check:**
1. Run `api/test_sync.php` to see actual status
2. Check if sync completed successfully
3. Verify remote database connection

**Solution:**
- Re-run sync
- If persists, use "Reset Local Sync Status" from undo_sync.php

### Problem: Remote ID is NULL even though is_synced = 1

**Possible Cause:**
- Sync might have been interrupted
- Remote record might have been deleted manually

**Solution:**
- Reset sync status for that record: `UPDATE table SET is_synced = 0 WHERE id = ?`
- Re-run sync

---

## Database Schema Requirements

### Required Columns (All Synced Tables)

```sql
-- UUID column
ALTER TABLE table_name ADD COLUMN uuid CHAR(36) NOT NULL DEFAULT '';
ALTER TABLE table_name ADD INDEX idx_uuid (uuid);

-- Sync status
ALTER TABLE table_name ADD COLUMN is_synced TINYINT(1) DEFAULT 0;

-- Remote ID mapping
ALTER TABLE table_name ADD COLUMN remote_id INT(11) DEFAULT NULL;
```

### Deleted Records Tracking

```sql
CREATE TABLE deleted_records (
    id INT(11) NOT NULL AUTO_INCREMENT,
    table_name VARCHAR(50) NOT NULL,
    uuid CHAR(36) NOT NULL,
    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_uuid (uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Note:** These are automatically created by `sync_data.php` if missing.

---

## API Endpoints

### 1. Sync Data
**Endpoint:** `POST api/sync_data.php`
**Authentication:** Required (session-based)
**Response:**
```json
{
  "success": true/false,
  "message": "Status message",
  "stats": {
    "users": 0,
    "functions": 0,
    "collections": 0,
    "expenses": 0,
    "deleted": 0
  }
}
```

### 2. Undo Sync
**Endpoint:** `POST api/undo_sync.php`
**Authentication:** Required
**Parameters:**
- `action`: "reset_local" or "delete_remote"

### 3. Test Sync
**Endpoint:** `GET api/test_sync.php`
**Authentication:** Required
**Response:** HTML diagnostic page

---

## Security Considerations

1. **Authentication Required**: All sync operations require valid user session
2. **Connection Timeout**: Prevents long-hanging connections (5s connect, 10s read)
3. **SQL Injection Prevention**: All queries use prepared statements
4. **UUID Validation**: Ensures unique tracking across systems
5. **Graceful Degradation**: System continues to work offline if remote is unavailable

---

## Performance Optimization

1. **Selective Sync**: Only syncs records with `is_synced = 0`
2. **Batch Processing**: Processes all pending records in one sync operation
3. **Connection Pooling**: Reuses database connections
4. **Indexed UUID**: Fast lookups using UUID index
5. **Smart Host Selection**: Uses localhost when on remote server

---

## Future Enhancements

- [ ] Automatic background sync (every X minutes)
- [ ] Conflict resolution for concurrent edits
- [ ] Sync progress indicator
- [ ] Partial sync (by table or date range)
- [ ] Sync history log
- [ ] Mobile app support with same sync mechanism

---

## Support

For issues or questions about synchronization:

1. Run `api/test_sync.php` and note any errors
2. Check browser console for JavaScript errors
3. Verify internet connection and remote server status
4. Contact system administrator with diagnostic information

---

**Last Updated:** 2026-01-19
**Version:** 2.0
**Author:** UZRS Development Team
