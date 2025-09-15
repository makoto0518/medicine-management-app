#medicine-management-app

開発環境: WampServer / PHP / MySQL / CSS  

---

## 動作方法
1. **WampServer** をダウンロード・インストール
2. WampServerを実行 
3. WampServer の MySQL で `CREATETABLE.sql` を実行し、必要なデータベースを構築
4. MySQLのusersテーブルに使うユーザーのID,username, passwordをinsert
5. localhost/このフォルダのディレクトリ/login.php を実行 insertしたユーザー情報を入力しログイン　自分の場合は http://localhost/inov/Medicine_2/login.php (makoto,1234)
---

## 解決したい課題
- 服用している薬の種類、服用数や時間が分からない
- 過去にどの薬を飲んだか分からない
- 服用タイミングを忘れてしまう
- 

---

## 機能概要

### 薬の登録
- **目的**: 薬の名前、服用時間、服用数、服用開始日、服用終了日を登録  
- もし登録された薬が今日服用しなければならないとき、ホーム画面に表示される。
- 服用記録を付け、後から日付ごとに見返して管理できる
- 必要ない薬は削除できる
---



