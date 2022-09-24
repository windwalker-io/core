<template>
<table class="table table-bordered">
  <thead>
  <tr>
    <th>
      Event Name
    </th>
    <th>
      Times
    </th>
    <th>
      Listener
    </th>
  </tr>
  </thead>
  <tbody>
  <template v-for="(listeners, event) of events" :key="event">
    <template v-if="Object.keys(listeners).length > 0">
      <tr v-for="(count, listener) of listeners">
        <td v-if="isFirstRow(event)" :rowspan="Object.keys(listeners).length">
          <pre>{{ event }}</pre>
        </td>
        <td>
          {{ count }}
        </td>
        <td>
          <pre>{{ listener }}</pre>
        </td>
      </tr>
    </template>
    <template v-else>
      <tr>
        <td>
          <pre>{{ event }}</pre>
        </td>
        <td>
          -
        </td>
        <td>
          -
        </td>
      </tr>
    </template>
  </template>
  </tbody>
</table>
</template>

<script>
export default {
  name: 'EventListenersTable',
  props: {
    events: Object,
  },
  setup() {
    let lastEvent = null;

    function isFirstRow(event) {
      const isFirst = event !== lastEvent;

      lastEvent = event;

      return isFirst;
    }

    return {
      isFirstRow,
    };
  }
};
</script>

<style scoped>

</style>
