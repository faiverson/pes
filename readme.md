## About PES
- **[Testing](https://pes.test/graphql-playground)**
### Debug MYSQL
```
$q = vsprintf(str_replace('?', "'%s'", $query->toSql()), $query->getBindings());
dd($q);
        
```
