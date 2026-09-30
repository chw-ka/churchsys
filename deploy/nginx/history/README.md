# nginx 設定歷史

`../default.conf` 是現行生效的設定。這裡保存的是先前版本的快照，供回滾參考。

| 檔案 | 說明 |
|---|---|
| `default.conf.bak_http_only` | 僅監聽 80 埠的初始版本，尚未設定 TLS 與 PWA |
| `default.conf.bak_20260930_pwa` | 已設定 TLS，但尚未加入 PWA 相關 location（`/sw.js`、`/manifest.json`、`/icons`） |

回滾方式：

```bash
cp deploy/nginx/history/default.conf.bak_20260930_pwa docker/nginx/default.conf
docker compose exec web nginx -t
docker compose exec web nginx -s reload
```

> 在伺服器上直接編輯 `docker/nginx/default.conf` 是沒有效果的——該檔案已納入版控，
> 下次 `git pull` 會覆蓋。請改在 repo 內修改並提交。
