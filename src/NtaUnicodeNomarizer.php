<?php

namespace Kanryu\NtaUnicodeNomarizer;

class NtaUnicodeNomarizer
{
    /** @var bool $convertKana 半角カナを強制的に全角カナに変換する */
    public $convertKana = true;

    /** @var callable $callback 取り除かれる文字列に対する処理のコールバック関数 */
    public $callback = null;
    
    /**
	 * 国税庁(National Tax Agency)が指定している文字以外を取り除き、可能なら他の文字に変換した形で返す(免税電子対応用).
	 *
	 * @param string $value 正規化後前のUnicode文字列
	 * @return string 正規化後のUnicode文字列
	 */
	public function normalize($value)
	{
		// 『免税販売管理システム API仕様書』 9p-10pより
		//
		// 免税販売管理システムが使用する文字コードは、JIS X 0221 を UTF8 で符号化したも
		// ののうち、JIS X 0201 と互換性のあるもの(基本ラテン(ただし、文字タブ(0009)、改
		// 行(000A)及び復帰(000D)以外の制御文字(0000~001F、007F)を除く))及び「平仮
		// 名」「片仮名」「CJK 統合漢字」「CJK 互換漢字」「CJK 用の記号及び分音記号」「半角形・全
		// 角形」 (ただし、半角カナ(FF66~FF9F)を除く)「ラテン-1補助(ただし、制御文字(0080
		// ~009F)を除く)」「矢印」「一般句読点」「罫線素片」「幾何学模様」「基本ギリシャ」「キ
		// リール」「数学記号」「数字の形」「囲み英数字」「囲み CJK 文字/月」「CJK 互換文字」と
		// します。

		// 半角カナを全角カナに変換
		if($this->convertKana) {
			$value = mb_convert_kana($value, 'KV', 'UTF-8');
		}
		$result = array();
		// UTF-8文字列を1文字ずつ取り出す
		foreach (preg_split("//u", $value) as $char) {
			// Unicode文字のコードポイント(グリフ番号)を計算する
			$code = hexdec(bin2hex(mb_convert_encoding($char, 'UCS-4', 'UTF-8')));
			switch($code) {
				case 0x0009: case 0x000a: case 0x000d:   // 制御記号
				case $code >= 0x0020 && $code <= 0x007e: // 基本ラテン
				case $code >= 0x3040 && $code <= 0x309f: // 平仮名
				case $code >= 0x30a0 && $code <= 0x30ff: // 片仮名
				case $code >= 0x4e00 && $code <= 0x9fff: // CJK統合漢字
				case $code >= 0xf900 && $code <= 0xfaff: // CJK互換漢字
				case $code >= 0x3000 && $code <= 0x303f: // CJK用の記号及び文書記号
				case $code >= 0xff00 && $code <= 0xff65: // 半角形・全角形(半角カナを除く)
				case $code >= 0xffa0 && $code <= 0xffef: // 半角形・全角形(半角カナを除く)
				case $code >= 0x00a0 && $code <= 0x00ff: // ラテン-1補助
				case $code >= 0x2190 && $code <= 0x21ff: // 矢印
				case $code >= 0x2000 && $code <= 0x206f: // 一般句読点
				case $code >= 0x2500 && $code <= 0x257f: // 罫線素片
				case $code >= 0x25a0 && $code <= 0x25ff: // 幾何学模様
				case $code >= 0x0370 && $code <= 0x03ff: // 基本ギリシャ
				case $code >= 0x0400 && $code <= 0x04ff: // キリール
				case $code >= 0x2200 && $code <= 0x22ff: // 数学記号
				case $code >= 0x2150 && $code <= 0x218f: // 数字の形
				case $code >= 0x2460 && $code <= 0x24ff: // 囲み英数字
				case $code >= 0x3200 && $code <= 0x32ff: // 囲みCJK文字/月
				case $code >= 0x3300 && $code <= 0x33ff: // CJK互換文字
					// 指定された文字は出力対象
					$result[] = $char;
				break;
				default:
					// 指定されていない文字は取り除く
					// コールバック関数が登録されている場合は呼び出す
					if ($this->callback) {
						$result[] = $this->callback($char, $code);
					}
				break;
			}
		}
		$resultString = implode('', $result);
		return $resultString;
	}
}





