---
description: 檢查生產系統整體健康狀態
allowed-tools: Bash(curl:*), Bash(ssh:*), Bash(dig:*)
---

對 ChurchSys 做一次端到端健康檢查，並以表格回報。

```bash
# DNS
dig +short churchsys.cmals.org A            # 應為 18.143.26.232
dig +short cmals.org A                       # 應不再指向 54.169.156.17

# 網站
curl -sS -o /dev/null -w 'login:  %{http_code}\n' https://churchsys.cmals.org/login
curl -sS -o /dev/null -w 'root:   %{http_code}\n' https://churchsys.cmals.org/

# 憑證到期
echo | openssl s_client -connect churchsys.cmals.org:443 -servername churchsys.cmals.org 2>/dev/null \
  | openssl x509 -noout -subject -dates
```

另外在伺服器上檢查：

```bash
ssh -i <key>.pem ubuntu@18.143.26.232 '
  cd /home/ubuntu/churchsys &&
  docker compose ps &&
  df -h / | tail -1 &&
  free -h | head -2 &&
  mount | grep s3fs &&
  sudo certbot certificates 2>/dev/null | grep -E "Certificate Name|Expiry"
'
```

回報表格請包含：項目 / 預期 / 實際 / 狀態。

需要留意的情況：

- `cmals.org` 仍指向 `54.169.156.17`（舊 EC2）→ DNS 轉向尚未完成，
  **不可關閉舊 EC2**。見 `docs/DEPLOYMENT.md` §5。
- s3fs 未掛載 → 會友相片無法顯示，`sudo mount /mnt/s3-drive`。
- 憑證剩餘少於 30 天 → 檢查 `certbot.timer` 是否正常運作。
