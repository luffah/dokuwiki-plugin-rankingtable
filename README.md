# Ranking Table plugin for DokuWiki


```
<rankingtable order by Score asc>
^ Id ^ Score ^
| A  | 3     |
| B  | 1     |
| C  | 2     |
</rankingtable>
```

will show

| Id  | Score |
|-----|-------|
| B   | 1     |
| C   | 2     |
| A   | 3     |


To get descending order use `desc` instead of `asc`.
The plugin doesn't support complex formatting.
However, you can use `**bold**`, `//italic//`, `__underlined__`, `<del>stroke</del>` in columns that are not used as sorting key.
