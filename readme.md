## About PES
PES is an API solution to create teams and handle games results.
It read data from a Google spreadsheet, also you can add data using end points.
It is using GraphQL as communication layer with the Front-End
There is a UI for this created in React. Link [here](https://github.com/juanpborda/pesui)

- **[Testing](https://pes.test/graphql-playground)**
### Debug MYSQL
```
$q = vsprintf(str_replace('?', "'%s'", $query->toSql()), $query->getBindings());
dd($q);
        
```

#Get Started
`php artisan migrate`
`php artisan db:seed --class=SeedTuesdays`
`php artisan pes:results --to=xx` (value from the last item)
`php artisan pes:results --from=xx --to=xx` (value from the last game in excel, to the last value included in excel)
