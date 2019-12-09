# WP GraphQL Offset Pagination

This is an extension for the [WPGraphQL](https://github.com/wp-graphql/wp-graphql) plugin for WordPress. It adds basic offset pagination as opposed to the standard [Cursor based pagination](https://docs.wpgraphql.com/getting-started/posts/#pagination) that ships with WPGraphQL.



## Installation

Download one of the releases from the release page, or just download `master` if you're feeling excitable.

Install and activate as you would any other WordPress plugin.



## Basic Usage

Using this plugin is pretty simple. If you were querying posts normally with Cursor based pagination, you would use something like this:

```
query GET_POSTS($first: Int, $after: String) {
  posts(first: $first, after: $after) {
    pageInfo {
      hasNextPage
      endCursor
    }
    edges {
      cursor
      node {
        id
        title
        date
      }
    }
  }
}
```



But with this plugin installed, you can use the following:

```
query GET_POSTS($page: Int!, $per_page: Int!) {
  posts(where: {offsetPagination: {page: $page, per_page: $per_page}}) {
    pageInfo {
      hasPreviousPage
      hasNextPage
      previousPage
      nextPage
      totalPages
    }
    edges {
      cursor
      node {
        id
        title
        date
      }
    }
  }
}
```



There are a few changes here. The first being that under the `where` query, there is a new `offsetPagination` object. This new `offsetPagination` object has two properties:

- `page` - The page number that you're requesting.
- `per_page` - The number of items you'd like per page.



There are also a few new fields that can be requested under the `pageInfo` field:

- `nextPage` - Contains an integer with the next page that you can load. Alternatively it will show `null` if there's no next page.
- `previousPage` - Contains an integer with the previous page that you can load. Alternatively it will show `null` if there's no previous page.
- `totalPages` - The total number of pages of results available to this query.

As a note, `hasPreviousPage` and `hasNextPage` will work as expected with this style of pagination.



## Support and Contributions

Although I'm releasing this publicly, I have ~~very little~~ no time to support this for the wider community due to current work commitments. If someone would like to pick it up and take ownership please get in contact. PR's are more than welcome also.