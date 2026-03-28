# 🔄 SYNC QUICK REFERENCE CARD

## 📋 Daily Checklist

- [ ] Morning: Run sync to get latest data
- [ ] During work: Save entries normally
- [ ] Before lunch: Quick sync
- [ ] End of day: Full sync
- [ ] Weekly: Check test_sync.php

## 🚀 Quick Actions

### Test Sync Status
```
http://your-domain/api/test_sync.php
```

### Run Sync Now
Click "☁️ Sync" button on any page

### Check Logs
View browser console (F12) during sync

## 🎯 What's Working

✅ **Offline Mode**: Enter data without internet
✅ **Auto UUID**: Every record gets unique ID
✅ **Smart Sync**: Only sends changed records
✅ **Delete Tracking**: Deletions also sync
✅ **Error Recovery**: Graceful failure handling
✅ **Tamil Support**: UTF-8MB4 encoding

## ⚡ Files Changed

### Updated
- `config/database.php` - Optimized connections

### Created
- `api/test_sync.php` - Diagnostic tool
- `docs/SYNC_DOCUMENTATION.md` - Full docs
- `docs/SYNC_CHECK_SUMMARY.md` - This summary

## 🔍 Quick Diagnostics

### Check if record will sync:
```sql
SELECT id, uuid, is_synced, remote_id 
FROM collections 
WHERE id = YOUR_ID;
```

Expected:
- uuid: Should have value (36 chars)
- is_synced: 0 = pending, 1 = synced
- remote_id: NULL or remote database ID

### Count pending records:
```sql
SELECT COUNT(*) FROM collections WHERE is_synced = 0;
```

### Check deleted records:
```sql
SELECT * FROM deleted_records;
```

## 🛠️ Troubleshooting

| Problem | Solution |
|---------|----------|
| Sync button unresponsive | Check browser console, verify login |
| "Cannot connect" error | Check internet, verify remote server |
| Records stay pending | Run test_sync.php, check for errors |
| Duplicate records | Should not happen; UUIDs prevent this |

## 📞 Emergency Actions

### Reset Sync Status:
```
Visit: api/undo_sync.php
Choose: "Reset Local Sync Status"
```

### Force Re-sync All:
```sql
UPDATE collections SET is_synced = 0;
UPDATE functions SET is_synced = 0;
UPDATE expenses SET is_synced = 0;
```
Then run sync.

## 🎓 Key Concepts

**UUID**: Unique ID that never changes
- Format: "67891234-a1b2c3d4e5f6"
- Same across local and remote
- Used to match records

**is_synced**: Status flag
- 0 = Has local changes
- 1 = Synced with remote

**remote_id**: Remote database ID
- Links local record to remote
- NULL if never synced

## ✨ Best Practices

1. **Sync Often**: Don't wait too long
2. **Check Status**: Use test tool weekly
3. **Stable Internet**: During sync
4. **Before Reports**: Always sync first
5. **Monitor Errors**: Note any messages

## 📊 Success Indicators

✅ test_sync.php shows all green
✅ Pending count is low (< 50)
✅ UUID coverage is 100%
✅ Remote connection: Success
✅ Recent syncs completing

## 🚨 Warning Signs

⚠️ Pending count > 200
⚠️ UUID coverage < 80%
⚠️ Remote connection fails
⚠️ Sync errors appearing
⚠️ Records with NULL UUID

## 📞 Get Help

1. Screenshot test_sync.php
2. Note error messages
3. Check browser console
4. Contact administrator

---

**Keep This Card Handy!**
Print and post near your workstation.

Last Updated: 2026-01-19
