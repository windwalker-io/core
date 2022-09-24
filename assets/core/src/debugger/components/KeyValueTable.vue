<template>
  <table class="table table-bordered">
    <thead>
    <tr>
      <th class="" style="width: 20%; min-width: 150px;" >
        Key
      </th>
      <th>
        Value
      </th>
    </tr>
    </thead>

    <tbody>
    <tr v-for="(value, key) of data" :key="key">
      <td style="width: 20%; word-break: break-all">
        {{ key }}
      </td>
      <td style="width: 80%; max-width: 800px">
        <pre style="word-break: break-all" class="m-0">{{ displayData(value) }}</pre>
      </td>
    </tr>
    </tbody>
  </table>
</template>

<script>
export default {
  name: 'KeyValueTable',
  props: {
    data: Object
  },
  setup() {
    function displayData(v) {
      if (typeof v === 'object' || Array.isArray(v)) {
        return JSON.stringify(v, null, 2);
      }

      return v;
    }

    return {
      displayData
    };
  }
};
</script>

<style scoped>

</style>
