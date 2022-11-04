<?php

namespace alviden\corrector\models;

use alviden\corrector\models\SearchHash;

/**
 * Класс, корректирующий орфографию и раскладку
 * Может использоваться, например, при поисковых запросах
 */
class Corrector
{
    /**
     * Минимальный вес, при котором слово будет исправляться на ближайшее найденное.
     * Чем больше значение, тем ниже вероятность, что слово будет исправлено
     */
    public $jwMinWeight = 0.8;

    /**
     * Разрядность карты хешей. Обычно используется значение, равное степени двойки
     * (8, 16, 32 итд)
     * Зависит от значений, указанных в hashMap
     * (максимальный элемент минус минимальный элемент)
     */
    public $quantityRanks = 16;

    /**
     * Карта, по которой собираются хеши.
     * Ключи - символы, которые кодируются в определенные разряды
     * Значения - позиция разрядов
     */
    public $hashMap = [
        'а' => 0, 'о' => 0, 'f' => 0, 'j' => 0,
        'б' => 1, 'п' => 1, ',' => 1, 'g' => 1,
        'в' => 2, 'ф' => 2, 'd' => 2, 'a' => 2,
        'г' => 3, 'к' => 3, 'х' => 3, 'u' => 3, 'r' => 3, '[' => 3,
        'д' => 4, 'т' => 4, 'l' => 4, 'n' => 4,
        'е' => 5, 'ё' => 5, 'э' => 5, 't' => 5, '`' => 5, '\''=> 5,
        'ж' => 6, 'ш' => 6, 'щ' => 6, ';' => 6, 'i' => 6, 'o' => 6,
        'з' => 7, 'с' => 7, 'p' => 7, 'c' => 7,
        'и' => 8, 'ы' => 8, 'й' => 8, 'b' => 8, 's' => 8, 'q' => 8,
        'л' => 9, 'р' => 9, 'k' => 9, 'h' => 9,
        'м' => 10, 'н' => 10, 'v' => 10, 'y' => 10,
        'у' => 11, 'ю' => 11, 'e' => 11, '.' => 11,
        'ц' => 12, 'ч' => 12, 'w' => 12, 'x' => 12,
        'ь' => 13, 'ъ' => 13, 'm' => 13, ']' => 13,
        'z' => 14, 'я' => 14,
    ];
    
    /**
     * Добавление нового слова
     * @param string $word слово
     * @return boolean успешность сохранения
     */
    public function addWord($word)
    {
        $hash = $this->sign($word);
        $sh = new SearchHash([
            'name' => $word,
            'hash' => $hash,
        ]);
        return $sh->save();
    }
    
    /**
     * Удаление слова
     * @param string $word слово
     * @return boolean успешность удаления
     */
    public function delWord($word)
    {
        $sh = SearchHash::findOne(['name' => $word]);
        if ($sh) {
            return $sh->delete();
        }
        return false;
    }

    /**
     * Возвращает нормализованное слово
     * @param string $word слово
     * @return string нормализованное слово
     */
    public function getCorrectWord($word)
    {
        $hash = $this->sign($word);

        $resArr = $this->getHashes($hash);
        $maxCoef = 0;
        $needName = '';
        foreach($resArr as $res) {
            $tempCoef = $this->jaroWinkler($res->name, $word);
            if ($maxCoef < $tempCoef) {
                $maxCoef = $tempCoef;
                $needName = $res->name;
            }
        }

        return ($maxCoef < $this->jwMinWeight) ? $word : $needName;
    }
    
    /**
     * Отображает ход поиска подходящего слова
     * Используется для проверки
     * @param string $word слово
     */
    public function findRes($word)
    {
        $hash = $this->sign($word);
        echo 'Current hash: ' . $hash . '<br>';
        echo '* * *<br>' ;
        $resArr = $this->getHashes($hash);

        $maxCoef = 0;
        foreach($resArr as $res) {
            $tempCoef = $this->jaroWinkler($res->name, $word);
            if ($maxCoef < $tempCoef) {
                $maxCoef = $tempCoef;
                $needName = $res->name;
                echo 'Current word: ' . $needName . '<br>';
                echo 'Current koef: ' . $maxCoef . '<br>';
                echo '***<br>' ;
            }
        }
    }
    
    /**
     * Возвращает массив хешей из бд
     * @param string $hash хеш, полученный от пользователя
     * @return array массив похожих записей
     */
    private function getHashes($hash)
    {
        $hashArr = preg_split('//u', (mb_strtolower($hash)), -1, PREG_SPLIT_NO_EMPTY);

        $resArr = [];
        foreach ($hashArr as $key => $oneHash) {
            $newHashArr = $hashArr;
            $newHashArr[$key] = $newHashArr[$key] ? 0 : 1;
            $resArr = array_merge($resArr, SearchHash::findAll(['hash' => implode('', $newHashArr)]));
        }

        return array_merge($resArr, SearchHash::findAll(['hash' => implode('', $newHashArr)]));
    }

        /**
     * хэширование по сигнатуре. На вход получает строку, на выходе хэш
     * @param string $q
     * @return string
     */
    private function sign($q) {

        $str = preg_split('//u', (mb_strtolower($q)), -1, PREG_SPLIT_NO_EMPTY);
        $resHashTable = array_fill(0, $this->quantityRanks, 0); //заполняем массив нулями

        foreach($str as $symb) {
            if (isset($this->hashMap[$symb]))
                    $resHashTable[$this->hashMap[$symb]] = 1;
        }
        return implode('', $resHashTable);

    }

    /**
     * сравнивает 2 строки, на выходе получаем вес
     * @param string $str1 слово, введенное пользователем
     * @param string $str2 искомое слово из словаря
     */
    private function jaroWinkler($str1, $str2) {
        $str1 = mb_strtolower($str1);
        $str2 = mb_strtolower($str2);
        //если вводимое слово и искомое слово в разных раскладках, вводимое слово приводим к искомому
        $firstSymbolStr1 = mb_substr($str1, 0, 1);
        $firstSymbolStr2 = mb_substr($str2, 0, 1);
        //если вводимое слово и искомое слово в разных раскладках, вводимое слово приводим к искомому
        if (($firstSymbolStr1 <= 'z' && $firstSymbolStr2 > 'z') || ($firstSymbolStr2 <= 'z' && $firstSymbolStr1 > 'z')) {
            $str1 = $this->puntoSwitcher($str1);
        }
        $s1 = mb_strlen($str1);
        $s2 = mb_strlen($str2); //длина строк
        if ($s1 < $s2) {
            $str1 = preg_split('//u', ($str1), -1, PREG_SPLIT_NO_EMPTY);
            $str2 = preg_split('//u', ($str2), -1, PREG_SPLIT_NO_EMPTY);
        }else {
            $tempStr1 = $str1;
            $str1 = preg_split('//u', ($str2), -1, PREG_SPLIT_NO_EMPTY);
            $str2 = preg_split('//u', ($tempStr1), -1, PREG_SPLIT_NO_EMPTY);
        }

        $m = 0; //кол-во совпадающих символов
        $t = 0; //число транзакций

        foreach ($str1 as $key1 => $symb1) {

            if ($symb1==$str2[$key1]) {
                $m++;
            }elseif(isset($str2[$key1-1]) && $symb1==$str2[$key1-1] || isset($str2[$key1+1]) && $symb1==$str2[$key1+1]) {
                $m++;
                $t++;
            }

        }
        $t /= 2;
        $dj = $m ? 1/3 * ($m/$s1 + $m/$s2 + ($m-$t)/$m) : 0;
        $dw = $dj + (0.3*(1 - $dj));

        return $dw;
    }

    /**
     * замена раскладки клавиатуры
     * @param string $str
     * @return string
     */
    private function puntoSwitcher($str) {
        $str = preg_split('//u', (mb_strtolower($str)), -1, PREG_SPLIT_NO_EMPTY);
        $_punto = [
            'a' => 'ф', 'b' => 'и', 'c' => 'с', 'd' => 'в', 'e' => 'у', 'f' => 'а', 'g' => 'п', 'h' => 'р', 'i' => 'ш', 'j' => 'о', 'k' => 'л', 'l' => 'д', 'm' => 'ь',
            'n' => 'т', 'o' => 'щ', 'p' => 'з', 'q' => 'й', 'r' => 'к', 's' => 'ы', 't' => 'е', 'u' => 'г', 'v' => 'м', 'w' => 'ц', 'x' => 'ч', 'y' => 'н', 'z' => 'я',
            '[' => 'х', ']' => 'ъ', ';' => 'ж', '\'' => 'э', ',' => 'б', '.' => 'ю'
        ];
        $res = '';
        foreach($str as $symb) {
            $res .= $_punto[$symb] ?? (array_search($symb, $_punto) ? : null) ?? $symb;
        }

        return $res;
    }

}
