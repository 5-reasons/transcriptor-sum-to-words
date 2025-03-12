# Converts numeric values into Latvian language words representation.

Supports integers and floats (treated as currency), negative values, and numbers up to 10^12 (triljons). For currency values, outputs "eiro" and "centi" denominations.

    $converter = new NumberToLatvianWordsConverter();

    echo $converter->convert(123.45);  
    // viens simts divdesmit trīs eiro un četrdesmit pieci centi

    echo $converter->convert(-1500000);  
    // mīnus viens miljons pieci simti tūkstoši

    echo $converter->convert(1000000000000);  
    // triljons
